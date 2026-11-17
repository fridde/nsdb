<?php

namespace App\EventSubscriber;

use App\Utils\Attributes\AbstractRunOn;
use App\Utils\Attributes\RunOnChange;
use App\Utils\Attributes\RunOnCreation;
use App\Utils\EntityObserver;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;


#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class UpdateSubscriber
{
    public function __construct(
        private readonly EntityObserver $eo,
        private readonly Security       $security,
        public LoggerInterface          $logger
    )
    {
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
        }, $args->getObjectManager()->getUnitOfWork()->getScheduledCollectionUpdates());

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

        if ($attribute->hasClass()) {
            $method = $attribute->getMethod();

            $rClass = new \ReflectionClass($attribute->getClass());
            $rMethod = $rClass->getMethod($method);

            $rMethod->invoke($rClass->newInstance(), $args);

            return;
        }

        $recordArgs = [
            'args' => $args,
            'entityName' => $entityName,
            'user' => $this->security->getUser()
        ];

        if($attribute instanceof RunOnChange){
            $recordArgs['propertyName'] = $propertyName ?? null;
            $recordArgs['isAssociative'] = $attribute->isAssociative;
            $recordArgs['isCollection'] = $attribute->isCollection;
        }

        $record = $this->eo->createRecord(...$recordArgs);
        $this->eo->addRecord($record);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        // TODO: Implement this method
    }


}
