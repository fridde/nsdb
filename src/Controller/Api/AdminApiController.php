<?php


namespace App\Controller\Api;


use App\Controller\Admin\Tool\BusController;
use App\Entity\Group;
use App\Entity\Location;
use App\Entity\School;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\Visit;
use App\Enums\Segment;
use App\Message\MessageBuilder;
use App\Message\MessageDetails;
use App\Message\MessageRecorder;
use App\Security\Key\ApiKeyManager;
use App\Security\Key\Key;
use App\Security\Role;
use App\Settings;
use App\Utils\RepoContainer;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackDividerBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackSectionBlock;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class AdminApiController extends AbstractController
{
    private ?Request $request;

    public function __construct(
        private readonly RequestStack                            $request_stack,
        private readonly EntityManagerInterface $em,
        private readonly RepoContainer          $rc,
        private readonly ApiKeyManager          $akm,
        private readonly DataApiController      $dataApiController,
        private readonly Settings               $settings,
        private readonly NotifierInterface      $notifier,
        private readonly ChatterInterface       $chatter,
        private readonly KernelInterface        $kernel,
        private readonly BusController          $busController
    )
    {
        $this->request = $request_stack->getCurrentRequest();
    }

    #[Route(
        '/api/register',
        methods: ['POST']
    )]
    #[IsGranted(Key::TYPE_ANON)]
    public function registerUser(): JsonResponse
    {
        $userData = $this->request->get('user');

        $user = new User();
        $this->dataApiController->updateSingleEntity($user, $userData);

        if($this->rc->getUserRepo()->findOneByMail($user->getMail()) instanceof User){
            throw new AlreadySubmittedException('A user with this mail address already exists.');
        }
        $this->em->flush();

        $this->sendApprovalMessageForUser($user);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/approve', methods: ['POST'])]
    #[IsGranted(Role::ACTIVE_USER)]
    public function approveUser(): JsonResponse
    {
        /** @var User $submittingUser */
        $submittingUser = $this->getUser();
        $isAdmin = $submittingUser->hasRole(Role::SUPER_ADMIN);
        $schoolId = $submittingUser->getSchoolId();
        $approvals = $this->request->get('approvals', []);
        foreach ($approvals as $value => $userIds) {
            if ($value === 'unsure') {
                continue;
            }
            foreach ($userIds as $userId) {
                $user = $this->em->find(User::class, (int)$userId);
                if (!($user instanceof User)) {
                    // TODO: log this pretty weird error
                    continue;
                }
                if (!$isAdmin && $user->getSchoolId() !== $schoolId) {
                    $f = 'The user\'s school "%s" was not the same as the submitter\'s school "%s". That\'s weird!';
                    throw new \LogicException(sprintf($f, $user->getSchoolId(), $schoolId));
                }
                if ($value === 'no') {
                    $user->setStatus(0);
                }
                if ($value === 'yes') {
                    $user->addRole(Role::ACTIVE_USER);
                }
            }
        }
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }


    #[Route('/api/schedule-colleague/{user}')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function scheduleColleague(User $user): Response
    {
        $visitId = $this->request->get('visit_id');
        $direction = $this->request->get('direction');

        /** @var Visit $visit */
        $visit = $this->rc->getVisitRepo()->find($visitId);

        if ($direction === 'add') {
            $visit->addColleague($user);
        } else {
            $visit->removeColleague($user);
        }
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/school-visit-order')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function updateSchoolVisitOrder(): Response
    {
        $schoolRepo = $this->rc->getSchoolRepo();

        $schoolOrder = $this->request->get('school-order');
        foreach ($schoolOrder as $key => $schoolId) {
            $school = $schoolRepo->find($schoolId);
            $school?->setVisitOrder($key + 1);
        }
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/set-bus-setting/{school}/{location}')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function setBusSetting(School $school, Location $location): Response
    {
        $direction = $this->request->get('direction');
        $needsBus = $direction === 'add';

        $school->updateBusRule($location, $needsBus);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/confirm-bus-order/{visit}')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function confirmBusOrder(Visit $visit): Response
    {
        $direction = $this->request->get('direction');
        $confirmed = $direction === 'confirm';

        $visit->setBusStatus($confirmed);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/distribute-visits')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function distributeVisits(): Response
    {
        $visits = $this->request->get('visits');
        $updates = [];
        foreach ($visits as $v) {
            $updates[$v['visit']] = ['Group' => $v['group']];
        }

        $this->dataApiController->updateMultipleEntities(Visit::class, $updates);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/get-valid-cron-key')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function getValidCronKey(): JsonResponse
    {
        $key = $this->akm->createKeyFromValues(Key::TYPE_CRON);
        $keyString = $this->akm->createCodeStringForKey($key);

        return new JsonResponse(['key' => $keyString]);
    }

    #[Route('/api/save-planned-visits')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function savePlannedVisits(): Response
    {
        $visits = $this->request->get('visits', []);
        $visits = $this->restructurePlannedVisitArray($visits);

        foreach ($visits as $letter => $dates) {
            $topic = $this->rc->getTopicRepo()->getTopicBySymbol($letter);
            assert($topic instanceof Topic);
            $cpg = $topic->getColleaguesPerGroup() ?? 1.0;

            foreach ($dates as $date => $colleagues) {
                [$assignedColleagues, $bystanders] = $colleagues;
                $nrOfVisitsThisDay = (count($assignedColleagues) / $cpg);
                if(fmod($nrOfVisitsThisDay,1 ) !== 0.0) {
                    $msg = sprintf('Aborted! The number of assigned colleagues for topic %s on %s did not match. Correct this mistake and save again.', strtoupper($letter), $date);
                    throw new LogicException($msg);
                }

                $combinedColleagues = [...$assignedColleagues, ...$bystanders];
                foreach (range(1, $nrOfVisitsThisDay) as $i) {
                    $visit = new Visit();
                    $visit->setDateString($date);
                    $visit->setTopic($topic);
                    foreach ($combinedColleagues as $userId) {
                        $user = $this->rc->getUserRepo()->find($userId);
                        assert($user instanceof User);
                        $visit->addColleague($user);
                    }
                    $this->em->persist($visit);
                }
            }
        }
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }


    #[Route('/api/get-waiting-mails')]
    #[IsGranted(Key::TYPE_CRON)]
    public function getWaitingMails(MessageBuilder $mb, MessageRecorder $mr): JsonResponse
    {
        $mails = $mb->collectAllMessages()
            ->map(fn(Email $m) => [
                'mail' => implode(' ; ', array_map(fn(Address $a) => $a->getAddress(), $m->getTo())),
                'subject' => $m->getSubject(),
                'body' => $m->getHtmlBody()
            ]);

        $transactionToken = $mr->saveTempRecordsToFile();

        return new JsonResponse(['mails' => $mails->getValues(), 'token' => $transactionToken]);
    }


    #[Route('/api/get-mail-subjects')]
    #[IsGranted(Key::TYPE_CRON)]
    public function getMailSubjects(Settings $settings): JsonResponse
    {
        return new JsonResponse(['subjects' => array_values($settings->get('mail_subjects'))]);
    }

    #[Route('/api/confirm-sent-mails')]
    #[IsGranted(Key::TYPE_CRON)]
    public function confirmSentMails(MessageRecorder $mr): JsonResponse
    {
        $status = $this->request->get('status');
        $token = $this->request->get('token');
        if ($status === 'success') {
            $mr->saveTempRecordsToDB($token);
        }
        $mr->removeTempRecords($token);
        return new JsonResponse([]);
    }

    #[Route('/api/add-groups')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function addMultipleGroups(): JsonResponse
    {
        $data = $this->request->get('data');
        $groupNumbers = $data['groupNumbers'];
        $segment = $data['segment'];
        $startYear = $data['startYear'];
        $letters = range('A', 'Z');
        $namePrefix = match($segment) {
            Segment::AK_2->value => '2',
            Segment::AK_5->value => '4'
        };
        $total = 0;

        foreach ($groupNumbers as $schoolId => $groupCount) {
            $groupCount = (int) $groupCount;
            if ($groupCount <= 0) {
                continue;
            }
            $school = $this->rc->getSchoolRepo()->find($schoolId);
            foreach (range(0, $groupCount - 1) as $i) {
                $group = new Group();
                $name = $namePrefix . ($groupCount === 1 ? ':orna' : $letters[$i]);
                $group->setName($name);
                $group->setSchool($school);
                $group->setSegment(Segment::from($segment));
                $group->setStartYear($startYear);
                $this->rc->getEntityManager()->persist($group);
                $total++;
            }
        }
        $this->rc->getEntityManager()->flush();

        return new JsonResponse(['groups_added' => $total]);
    }

    #[Route('/api/batch-rename-groups')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function renameMultipleGroups(): JsonResponse
    {
        $data = $this->request->get('data', []);

        foreach($data as $group){
            $id = $group['group'];
            $newName = $group['name'];

            $group = $this->rc->getGroupRepo()->find($id);
            /** @var Group $group  */
            $group->setName($newName);
            $this->rc->getEntityManager()->persist($group);
        }
        $this->rc->getEntityManager()->flush();

        return new JsonResponse(['groups_names_changed' => count($data)]);
    }

    #[Route('/api/save-multi-access-user/{user}')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function saveMultiAccessUser(User $user): JsonResponse
    {
        $key = 'users_with_access_to_multiple_schools';
        $schools = array_filter(explode(',', $this->request->get('schools') ?? ''));
        $users = $this->settings->get($key);

        $users[$user->getId()] = $schools;
        $this->settings->save($key, array_filter($users));

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/change-editable-setting')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function changeEditableSetting(): JsonResponse
    {
        $key = $this->request->get('setting'); // can't use "key" due to security controller
        $value = $this->request->get('value');
        $this->settings->save($key, $value);

        return new JsonResponse(['success' => true]);

    }

    #[Route('/api/save-bus-data')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function updateUnknownLocations(): JsonResponse
    {
        $settings = $this->busController->getSettings();

        $locations = $this->request->get('locations');
        if(!empty($locations)){
            $settings['locations'] ??= [];
            $settings['locations'] += $locations;
        }
        $url = $this->request->get('url');
        if(!empty($url)){
            $today = Carbon::today()->toDateString();
            $settings['urls'][$today] ??= [];
            $settings['urls'][$today][] = $this->busController->cleanUrl($url);
            $settings['urls'][$today] = array_unique($settings['urls'][$today]);
        }
        $this->busController->writeToSettings($settings);
    }

    private function sendApprovalMessageForUser(User $user): void
    {
        $approvalUrl = $this->generateUrl(
            'school_overview',
            ['school' => $user->getSchool()->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL);

        $chatMessage = new ChatMessage('User approval');

        $options = (new SlackOptions())
            ->block((new SlackSectionBlock())
                ->text($user->getFullName() . ' ('. $user->getMail() . ') ansöker om behörighet för ' . $user->getSchool()->getName())
            )
            ->block(new SlackDividerBlock())
            ->block((new SlackSectionBlock())->text('Kontrollera detta via Outlook eller annat lämpligt verktyg.'))
            ->block(new SlackDividerBlock())
            ->block((new SlackSectionBlock())->text('Bekräfta sedan via ' . $approvalUrl));

        $chatMessage->options($options);

        $this->chatter->send($chatMessage);
    }

    private function restructurePlannedVisitArray(array $visits): array
    {
        $s = [];
        foreach ($visits as $key => $value) {
            [$d, $c] = explode('_', $key);
            [$l, $b] = explode('_', $value);
            $c = (int) $c;
            $b = ((int) $b === 0 ? 1 : 0); // invert 1 to 0 and vice versa

            $dates = $s[$l] ?? [];
            $colleagues = $dates[$d] ?? [[], []];  // 0 => assigned, 1 => bystanders
            $colleagues[$b][] = $c;
            $s[$l][$d] = $colleagues;
        }

        return $s;
    }


}