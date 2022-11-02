<?php


namespace App\Controller\Api;


use App\Entity\Location;
use App\Entity\School;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\Visit;
use App\Message\MessageBuilder;
use App\Message\MessageDetails;
use App\Message\MessageRecorder;
use App\Security\Key\ApiKeyManager;
use App\Security\Key\Key;
use App\Security\Role;
use App\Settings;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;


class AdminApiController extends AbstractController
{
    private ?Request $request;

    public function __construct(
        RequestStack                   $request_stack,
        private EntityManagerInterface $em,
        private RepoContainer          $rc,
        private ApiKeyManager          $akm,
        private DataApiController      $dataApiController,
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
        $this->em->flush();

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
                $nrOfVisitsThisDay = (count($colleagues) / $cpg);
                foreach (range(1, $nrOfVisitsThisDay) as $i) {
                    $visit = new Visit();
                    $visit->setDateString($date);
                    $visit->setTopic($topic);
                    foreach ($colleagues as $userId) {
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
        if($status === 'success'){
            $mr->saveTempRecordsToDB($token);
        }
        $mr->removeTempRecords($token);
        return new JsonResponse([]);
    }

    private function restructurePlannedVisitArray(array $visits): array
    {
        $s = [];
        foreach ($visits as $visit) {
            $l = $visit['letter'];
            $d = $visit['date'];
            $c = $visit['colleague'];
            $dates = $s[$l] ?? [];
            $colleagues = $dates[$d] ?? [];
            $colleagues[] = (int)$c;
            $s[$l][$d] = $colleagues;
        }

        return $s;
    }


}