<?php


namespace App\Controller\Admin\Tool;


use App\Entity\Group;
use App\Entity\School;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\Visit;
use App\Enums\Segment;
use App\Message\MessageBuilder;
use App\Message\MessageRecorder;
use App\Settings;
use App\Utils\RepoContainer;
use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class ToolController extends AbstractController
{
    public function __construct
    (
        private readonly RepoContainer $rc,
        private readonly RequestStack $request
    )
    {
    }


    #[Route('/admin/confirm-bus-orders', name: 'tools_confirm_bus_orders')]
    #[Template('admin/tools/confirm_bus_orders.html.twig')]
    public function confirmBusOrders(): array
    {

        $visits = $this->rc->getVisitRepo()->getActiveVisitsAfterToday();
        $data['visits'] = $visits->filter(fn(Visit $v) => $v->needsBus());
        $data['colors'] = $this->calculateColorIndexForVisits($data['visits']);

        return $data;
    }

    #[Route('/admin/distribute-visits/{topic}', name: 'tools_distribute_visits')]
    #[Template('admin/tools/distribute_visits.html.twig')]
    public function distributeVisits(?Topic $topic = null): array
    {
        // TODO: Allow to filter for start-year. You don't want the current groups to appear here
        $topics = $this->rc->getTopicRepo()->getActiveTopics();
        if ($topic !== null) {
            $segment = $topic->getSegment();
            assert($segment instanceof Segment);
            $startYear = Carbon::today()->addDays(60)->year;

            $visits = $this->rc->getVisitRepo()->getActiveVisitsWithTopic($topic);
            $orphanedVisits = $visitsForGroups = [];
            foreach ($visits as $visit) {
                /** @var Visit $visit */
                if ($visit->hasGroup()) {
                    $visitsForGroups[$visit->getGroup()?->getId()] = $visit;
                } else {
                    $orphanedVisits[] = $visit;
                }
            }

            $groups = $this->rc->getGroupRepo()->getActiveGroupsFromSegmentWithStartYear($segment, $startYear);
            $chosenVisits = [];
            foreach ($groups as $group) {
                /** @var Group $group */
                $visit = $visitsForGroups[$group->getId()] ?? null;
                $chosenVisits[] = [$group->getId(), $visit?->getId()];
            }

            $visitColors = $this->calculateColorIndexForVisits($visits);
            $groupColors = $this->calculateColorIndexForGroups($groups);

            $data = [
                'visits' => $visits,
                'visits_for_groups' => $visitsForGroups,
                'orphaned_visits' => $orphanedVisits,
                'groups' => $groups,
                'chosen_visits' => $chosenVisits,
                'visit_colors' => $visitColors,
                'group_colors' => $groupColors,
                'this_topic' => $topic
            ];
        }

        return ['topics' => $topics] + ($data ?? []);
    }

    #[Route('/admin/plan-year', name: 'tools_plan_year')]
    #[Template('admin/tools/plan_year.html.twig')]
    public function planYear(): array
    {
        /** @var Carbon $monday */
        $monday = Carbon::today()->locale('sv_SE')->startOfWeek();
        $lastDayThisYear = Carbon::today()->month(12)->day(31);
        $data['days_left_this_year'] = $monday->diffInDays($lastDayThisYear);
        $dates = $monday->daysUntil(Carbon::today()->addDays(400))->toArray();
        $dates = array_filter($dates, fn(Carbon $d) => $d->isWeekday());
        $data['dates'] = $dates;

        $data['existing_visits'] = [];

        $visits = $this->rc->getVisitRepo()->getActiveVisitsAfter($monday);
        foreach($visits as $visit){
            /** @var Visit $visit  */
            foreach($visit->getColleagues() as $user){
                /** @var User $user  */
                $key = $visit->getDateString() . '_' . $user->getId();
                $data['existing_visits'][$key] = $visit->getTopic()->getSymbol() . '_1';  // we can't know which one of the colleagues will be a bystander
            }
        }



        $topics = array_filter($this->rc->getTopicRepo()->findAll(), fn(Topic $t) => $t->hasSymbol() && $t->isActive());
        $topics = array_map(fn(Topic $t) => [$t->getSymbol(), $t->getColleaguesPerGroup(), $t->getSegment()->value], $topics);
        $data['topics'] = array_combine(
            array_column($topics, 0),
            array_map(fn($v) => ['cpg' => $v[1], 'segment' => $v[2]], $topics)   // cpg means "colleagues per group"
        );

        $data['colleagues'] = $this->rc->getUserRepo()->getColleagues();
        $data['first_cell_id'] = $monday->toDateString() . '_' . $data['colleagues']->first()->getId();

        return $data;
    }

    #[Route('/admin/order-bus', name: 'tools_order_bus')]
    #[Template('admin/tools/order_bus.html.twig')]
    public function orderBus(): array
    {
        Carbon::setLocale('sv');
        $visits = $this->rc->getVisitRepo()->getActiveVisitsAfterToday();
        $data['visits'] = $visits->filter(fn(Visit $v) => $v->needsBus());

        return $data;
    }

    #[Route('/admin/order-food', name: 'tools_order_food')]
    #[Template('admin/tools/order_food.html.twig')]
    public function orderFood(): array
    {
        $visits = $this->rc->getVisitRepo()->getActiveVisitsAfterToday();
        $data['visits'] = $visits->filter(fn(Visit $v) => $v->needsFood())->getValues();

        return $data;
    }

    #[Route('/admin/schedule-colleagues', name: 'tools_schedule_colleagues')]
    #[Template('admin/tools/schedule_colleagues.html.twig')]
    public function scheduleColleagues(): array
    {
        $data['colleagues'] = $this->rc->getUserRepo()->getColleagues();

        $visits = $this->rc->getVisitRepo()->getActiveVisitsAfterToday();
        $data['colors'] = $this->calculateColorIndexForVisits($visits);
        $data['visits'] = $visits;

        return $data;
    }

    #[Route('/admin/send-mail', name: 'tools_send_mail')]
    #[Template('admin/tools/send_mail.html.twig')]
    public function sendMail(MessageBuilder $mb): array
    {
        $data['messages'] = $mb->collectAllMessages();

        return $data;
    }

    #[Route('/admin/set-bus-settings', name: 'tools_set_bus_settings')]
    #[Template('admin/tools/bus_settings.html.twig')]
    public function setBusSettings(): array
    {
        $locationRepo = $this->rc->getLocationRepo();
        $schoolRepo = $this->rc->getSchoolRepo();

        $data['locations'] = $locationRepo->getActiveLocations();
        $data['schools'] = $schoolRepo->getActiveSchools();

        return $data;
    }

    #[Route('/admin/order-schools', name: 'tools_order_schools')]
    #[Template('admin/tools/order_schools.html.twig')]
    public function orderSchools(): array
    {
        $schools = $this->rc->getSchoolRepo()->getActiveSchoolsByVisitOrder();

        return ['schools' => $schools];
    }

    #[Route('/admin/show-log', name: 'tools_show_log')]
    #[Template('admin/tools/show_log.html.twig')]
    public function showLog(): array
    {
        // TODO: Implement this tool
        return [];
    }

    #[Route('/admin/add-groups', name: 'tools_add_groups')]
    #[Template('admin/tools/add_groups.html.twig')]
    public function addMultipleGroups(string $entity = null): array
    {
        $years = [
            Segment::AK_2->value => Carbon::today()->year,
            Segment::AK_5->value => Carbon::today()->subYear()->year
        ];

        $groups = array_fill_keys(array_keys($years), []);  // '2' => [], '5' => [], etc

        $schools = $this->rc->getSchoolRepo()->getActiveSchools();
        $data = ['schools' => $schools, 'all_groups' => $groups, 'years' => $years];

        foreach($schools as $school){
            /** @var School $school  */
            foreach($years as $segmentValue => $year){
                $segment = Segment::from($segmentValue);
                $data['all_groups'][$segmentValue][$school->getId()] = $school->getActiveGroupsBySegmentAndYear($segment, $year)->count();
            }
        }

        return $data;
    }

    #[Route('/admin/batch-edit/{entity}', name: 'tools_batch_edit')]
    #[Template('admin/tools/batch_edit.html.twig')]
    public function batchEdit(string $entity = null): array
    {
        $data = ['entities' => null, 'type' => null];

        if ($entity === 'group') {
            $data['type'] = 'group';
            $data['segments'] = Segment::getLabels();

            $groups = $this->rc->getGroupRepo()->isActive()->getMatching();
            $entities = $groups->sortByFunction(function (Group $g1, Group $g2){
                // see elvis-operator and spaceship operator
                return $g1->getStartYear() <=> $g2->getStartYear()
                    ?: $g1->getSegment()?->getOrder() <=> $g2->getSegment()?->getOrder()
                        ?: $g1->getSchool()->getId() <=> $g2->getSchool()->getId()
                            ?: $g1->getName() <=> $g2->getName();
            });


            $data['years'] = $entities->map(fn(Group $g) => $g->getStartYear())
                ->unique()
                ->sortByFunction(fn(int $y1, int $y2) => $y1 <=> $y2)
                ->toArray();
            $data['entities'] = $entities->toArray();

            return $data;
        }

        if ($entity === 'user') {
            $data['type'] = 'user';
            $data['entities'] = $this->rc->getUserRepo()->isActive()->getMatching();

            return $data;
        }

        return $data;
    }

    #[Route('/admin/create-api-keys', name: 'tools_create_api_keys')]
    #[Template('admin/tools/create_api_keys.html.twig')]
    public function createApiKeys(): array
    {
        // Yes, this is all!
        return [];
    }

    #[Route('/admin/extra-settings', name: 'tools_extra_settings')]
    #[Template('admin/tools/extra_settings.html.twig')]
    public function editExtraSettings(Settings $settings): array
    {
        $data['next_school_admin_mail'] = $settings->get('next_school_admin_mail');

        $data['users_with_access_to_multiple_schools'] = $settings->get('users_with_access_to_multiple_schools') ?? [];
        $data['users'] = $this->rc->getUserRepo()
            ->isActive()->getMatching()
            ->withKey(fn(User $u) => $u->getId());
        $data['schools'] = $this->rc->getSchoolRepo()->getActiveSchools();

        return $data;
    }

    private function calculateColorIndexForVisits(Collection $visits): array
    {
        $r = [];
        foreach ($visits as $visit) {
            /** @var Visit $visit */
            $date = $visit->getDate();
            $weekIsOdd = $date->isoWeek % 2 === 0 ? 0 : 1;
            $r[$visit->getId()] = $date->dayOfWeekIso + ($weekIsOdd * 5) - 1;
        }
        return $r;
    }

    private function calculateColorIndexForGroups(Collection $groups): array
    {
        $r = [];
        foreach ($groups as $group) {
            /** @var Group $group */
            $r[$group->getId()] = $group->getSchool()->getVisitOrder() % 10;
        }

        return $r;
    }


}