<?php


namespace App\Utils;


use App\Entity\CalendarEvent;
use App\Entity\Group;
use App\Entity\Record;
use App\Entity\User;
use App\Entity\Visit;
use App\Repository\Filterable;
use App\Repository\RecordRepository;
use App\Settings;
use App\Utils\Attributes\TaskMethod;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use ReflectionAttribute;
use ReflectionMethod;

class TaskManager
{
    private RecordRepository $recordRepo;
    private array $taskToMethods = [];
    private array $lastExecutionTimes = [];

    private const RELEVANT_EVENTS = [
        Visit::class => [
            'visit_created',
            'visit_colleagues_changed',
            'visit_group_changed',
            'visit_date_changed',
            'visit_confirmed_changed',
            'visit_status_changed'
        ],
        CalendarEvent::class => [
            'calendarevent_created',
            'calendarevent_title_changed',
            'calendarevent_status_changed'
        ],
        User::class => [
            'user_mobil_changed',
            'user_mail_changed'
        ],
        Group::class => [
            'group_info_changed',
            'group_numberstudents_changed',
            'group_name_changed',
            'group_user_changed',
            'group_status_changed'
        ]
    ];


    public function __construct(
        private Settings      $settings,
        private RepoContainer $rc,
        private Calendar      $calendar
    )
    {
        $this->recordRepo = $this->rc->getRecordRepo();
    }

    public function getLastExecution(string $taskName): Carbon
    {
        if (!empty($this->lastExecutionTimes[$taskName] ?? null)) {
            return $this->lastExecutionTimes[$taskName];
        }

        $record = $this->recordRepo
            ->addAndFilter('Type', $taskName)
            ->addOrder('Created', Criteria::DESC)
            ->limitBy(1)
            ->getMatching()->first();

        if ($record instanceof Record) {
            $dt = $record->getCreated();
        } else {
            $dt = Carbon::create(1900); // default "long time ago"
        }
        $this->lastExecutionTimes[$taskName] = $dt;

        return $dt;
    }

    public function longEnoughSinceLastExecution(string $taskName): bool
    {
        $intervalString = $this->settings->get('task_frequency.' . $taskName);
        $interval = CarbonInterval::create($intervalString);

        return $this->getLastExecution($taskName)->copy()->add($interval)->lt(Carbon::now());
    }

    public function getTaskToMethods(): array
    {
        if(!empty($this->taskToMethods)){
            return $this->taskToMethods;
        }

        $class = new \ReflectionClass($this);

        foreach ($class->getMethods() as $method) {
            /** @var ReflectionAttribute $att */
            $att = array_values($method->getAttributes(TaskMethod::class))[0] ?? null;

            if ($att !== null) {
                /** @var TaskMethod $taskMethod */
                $taskMethod = $att->newInstance();
                $this->taskToMethods[$taskMethod->getName()] = $method;
            }
        }
        return $this->taskToMethods;

    }

    public function getTaskNames(): array
    {
        return array_keys($this->getTaskToMethods());
    }

    public function getMethodForTask(string $taskName): ReflectionMethod
    {
        return $this->getTaskToMethods()[$taskName];
    }

    public function execute(string $taskName): void
    {
            $method = $this->getMethodForTask($taskName);
            $result = $method->invoke($this);

            if(is_array($result)){
                $record = new Record($taskName);
                $record->setContent($result);
                $this->rc->getEntityManager()->persist($record);
                $this->rc->getEntityManager()->flush();
            }
    }

    #[TaskMethod('update_calendar')]
    public function updateCalendar(): ?array
    {
        $entityIds = array_fill_keys([Visit::class, CalendarEvent::class], []);
        foreach(array_keys(self::RELEVANT_EVENTS) as $entityClass){
            $returnType = $this->getReturnTypeForEnitityClass($entityClass);
            $entityIds[$returnType] += $this->collectUpdatableEntityIds($entityClass);
            $entityIds[$returnType] = array_values(array_unique($entityIds[$returnType]));
        }
        $entityIds = array_filter($entityIds);

        $this->updateEntitiesInCalendar($entityIds);

        return empty($entityIds) ? null : $entityIds;
    }

    private function getReturnTypeForEnitityClass(string $entityClass): string
    {
        return match ($entityClass){
          Group::class, User::class => Visit::class,
          default => $entityClass
        };
    }

    public function collectUpdatableEntityIds(string $class): array
    {
        $lastExecution = $this->getLastExecution('update_calendar');
        $matchingTypes = self::RELEVANT_EVENTS[$class];

        $records = $this->recordRepo
            ->addAndFilter('Type', $matchingTypes, Comparison::IN)
            ->addAndFilter('Created', $lastExecution, Comparison::GTE)
            ->getMatching();

        return $records
            ->map(fn(Record $r) => $this->getIdsFromRecord($r, $class))
            ->collapse()->unique()->toArray();
    }


    private function updateEntitiesInCalendar(array $entityIds): void
    {
        foreach($entityIds as $entityClass => $ids){
            /** @var Filterable $repo */
            $repo = match ($entityClass) {
                Visit::class => $this->rc->getVisitRepo(),
                CalendarEvent::class => $this->rc->getCalendarEventRepo(),
            };

            $allEntities = $repo
                ->addAndFilter('id', array_unique($ids), Comparison::IN)
                ->getMatching()->toArray();

            foreach ($allEntities as $entity) {
                $this->calendar->syncEventWithEntity($entity);
            }
        }
    }

    private function getShortClassName(string $class): string
    {
        return match ($class) {
            Visit::class => 'visit',
            CalendarEvent::class => 'calendarevent'
        };
    }

    private function getIdsFromRecord(Record $record, string $class): array
    {
        return match($class){
          Visit::class, CalendarEvent::class => [$record->getFromContent($this->getShortClassName($class))],
          Group::class => $this->getVisitIdsForGroupRecord($record),
          User::class => $this->getVisitIdsForUserRecord($record)
        };
    }

    private function getVisitIdsForGroupRecord(Record $record): array
    {
        /** @var Group $group  */
        $group = $this->rc->getGroupRepo()->find($record->getFromContent('group'));

        return $group->getVisits()->map(fn(Visit $v) => $v->getId())->toArray();
    }

    private function getVisitIdsForUserRecord(Record $record): array
    {
        /** @var User $user  */
        $user = $this->rc->getUserRepo()->find($record->getFromContent('user'));

        return $user->getAllVisits()->map(fn(Visit $v) => $v->getId())->toArray();
    }




    /*    #[TaskMethod('check_new_pending_users')]
        public function checkNewPendingUsers(): void
        {

        }

        #[TaskMethod('check_admin_summary')]
        public function checkSummaryAndNotifyAdmin(): void
        {

        }*/
}