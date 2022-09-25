<?php /** @noinspection PhpUnnecessaryLocalVariableInspection */


namespace App\Utils;


use App\Entity\Record;
use App\Entity\User;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EntityObserver
{
    private array $pendingRecords = [];

    public function __construct(private RepoContainer $rc, public LoggerInterface $logger)
    {
    }

    public function createRecord(
        LifecycleEventArgs $args,
        string $entityName,
        ?UserInterface $user = null,
        string $propertyName = null,
        bool $isAssociative = null,
        bool $isCollection = null
    ): Record
    {
        $entity = $args->getObject();
        $action = (empty($propertyName) ? 'created' : 'changed');

        $recordType = mb_strtolower($entityName);
        if ($action === 'changed') {
            $recordType .= '_' . mb_strtolower($propertyName);
        }
        $recordType .= '_' . $action;
        $record = new Record($recordType);
        $record->addToContent(mb_strtolower($entityName), $entity->getId());
        if ($action === 'changed' && !$isCollection) {
            $oldValue = $args->getOldValue($propertyName);
            if ($isAssociative && $oldValue !== null && method_exists($oldValue, 'getId')) {
                $oldValue = $oldValue->getId();
            }
            $record->addToContent('old_value', $oldValue);
        }
        if($user instanceof User){
            $record->setUser($user);
        }
        return $record;
    }

    public function savePendingRecords(): void
    {
        if (empty($this->pendingRecords)) {
            return;
        }

        // should *only* be called during postFlush
        foreach ($this->pendingRecords as $record) {
            $this->rc->getEntityManager()->persist($record);
        }
        $this->pendingRecords = [];  // we don't want to repeat this process in case the updateSubscriber runs again
        $this->rc->getEntityManager()->flush();
    }

    public function addRecord(Record $record): void
    {
        $this->pendingRecords[] = $record;
    }
}