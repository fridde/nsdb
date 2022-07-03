<?php

namespace App\EventSubscriber;

use App\Utils\Attributes\AbstractRunOn;
use App\Utils\Attributes\RunOnChange;
use App\Utils\Attributes\RunOnCreation;
use App\Utils\EntityObserver;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use ReflectionAttribute;

class UpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityObserver $eo, public LoggerInterface $logger)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preUpdate,
            Events::postRemove,
            Events::postFlush
        ];
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->eo->savePendingRecords();
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $rClass = new \ReflectionClass($args->getObject());
        $attribute = array_values($rClass->getAttributes(RunOnCreation::class))[0] ?? null;
        if($attribute === null){
            return;
        }

        $this->runMethod($attribute->newInstance(), $args, $rClass);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $updatedCollectionNames = array_map(function (PersistentCollection $update) {
            return $update->getMapping()['fieldName'];
        }, $args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates());

        $rClass = new \ReflectionClass($args->getObject());

        foreach ($rClass->getProperties() as $property) {
            $attribute = array_values($property->getAttributes(RunOnChange::class))[0] ?? null;
            $propertyName = $property->getName();
            if (
                $attribute !== null
                && ($args->hasChangedField($propertyName)
                    || in_array($propertyName, $updatedCollectionNames, true))
            ) {
                $att = $attribute->newInstance();
                $this->runMethod($att, $args, $property);
            }
        }
    }

    private function runMethod(AbstractRunOn $attribute, LifecycleEventArgs $args, $target): void
    {
        if($target instanceof \ReflectionProperty){
            $entityName = $target->getDeclaringClass()->getShortName();
            $propertyName = $target->getName();
        } else if ($target instanceof \ReflectionClass){
            $entityName = $target->getShortName();
        } else {
            throw new \InvalidArgumentException('parameter $target must be Reflection-class or -property');
        }

        $class = $attribute->getClass();

        if ($class === null && $attribute instanceof AbstractRunOn) {

            $recordArgs = [
                'args' => $args,
                'entityName' => $entityName,
            ];

            if($attribute instanceof RunOnChange){
                $recordArgs['propertyName'] = $propertyName ?? null;
                $recordArgs['isAssociative'] = $attribute->isAssociative;
                $recordArgs['isCollection'] = $attribute->isCollection;
            }

            $record = $this->eo->createRecord(...$recordArgs);
            $this->eo->addRecord($record);
            return;
        }
        $method = $attribute->getMethod();

        $rClass = new \ReflectionClass($class);
        $rMethod = $rClass->getMethod($method);

        $rMethod->invoke($rClass->newInstance(), $args);

    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        // TODO: Implement this method
    }


}
