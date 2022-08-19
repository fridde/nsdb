<?php


namespace App\Utils;


use App\Entity\CalendarEvent;
use App\Entity\Record;
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

    public function __construct(
        private Settings $settings,
        private RepoContainer $rc,
        private Calendar $calendar
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

        return $this->getLastExecution($taskName)->add($interval)->lt(Carbon::now());
    }

    public function collectAllMethods(): array
    {
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

    public function execute(string $taskName): bool
    {
        try {
            /** @var ReflectionMethod $method */
            $method = $this->taskToMethods[$taskName];
            $method->invoke($this);
        } catch (\Exception $e) {
            return false;
        }
        $record = new Record($taskName);
        $this->rc->getEntityManager()->persist($record);
        $this->rc->getEntityManager()->flush();

        return true;
    }

    #[TaskMethod('update_calendar')]
    public function updateCalendar(): void
    {
        // the first value will be used for inserts
        $matchingTypesVisit = ['visit_created', 'visit_colleagues_changed']; // TODO : add more recording-types
        $matchingTypesCE = ['calendarevent_created'];

        $this->updateEntitiesInCalendar($matchingTypesVisit, 'visit');
        $this->updateEntitiesInCalendar($matchingTypesCE, 'calendarevent');
    }


    private function updateEntitiesInCalendar(array $matchingTypes, string $idKey): void
    {
        $getIdFromValue = fn(Record $r) => $r->getFromContent($idKey);

        $lastExecution = $this->getLastExecution('update_calendar');

        $records = $this->recordRepo
            ->addAndFilter('Type', $matchingTypes, Comparison::IN)
            ->addAndFilter('Created', $lastExecution,  Comparison::GTE)
            ->getMatching();

        $splitRecords = $records->partition(fn($k, Record $r) => $r->isType($matchingTypes[0]));

        /** @var ExtendedCollection[] $splitRecords */
        $creations = $splitRecords[0]->map($getIdFromValue);
        $updates = $splitRecords[1]
            ->map($getIdFromValue)
            ->filter(fn($id) => !$creations->contains($id));

        /** @var Filterable $repo  */
        $repo = match($idKey){
            'visit' => $this->rc->getVisitRepo(),
            'calendarevent' => $this->rc->getCalendarEventRepo(),
        };
        $allEntityIds = array_unique($records->map($getIdFromValue)->toArray());
        $allEntities = $repo->addAndFilter('id', $allEntityIds, Comparison::IN)
            ->getMatching()->toArray();
        $keys = array_map(fn(CalendarEvent|Visit $e)=> $e->getId(), $allEntities);
        $allEntities = array_combine($keys, $allEntities);

        foreach($updates as $updatedEntityId){
            $entity = $allEntities[$updatedEntityId];
            $this->calendar->updateEventForEntity($entity);
        }

        foreach($creations as $createdEntityId){
            $entity = $allEntities[$createdEntityId];
            $this->calendar->insertEventForEntity($entity);
        }
    }

    #[TaskMethod('check_new_pending_users')]
    public function checkNewPendingUsers(): void
    {

    }

    #[TaskMethod('check_admin_summary')]
    public function checkSummaryAndNotifyAdmin(): void
    {

    }
}