<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\Topic;
use App\Entity\Visit;
use App\Utils\Attributes\FilterMethod;
use App\Utils\ExtendedCollection;
use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Comparison;

class VisitRepository extends EntityRepository
{
    use Filterable;

    #[FilterMethod('after-today')]
    public function afterToday(): self
    {
        $today = Carbon::today()->toDateString();
        return $this->addAndFilter('Date', $today,  Comparison::GTE);
    }

    public function hasTopic(Topic $topic = null): self
    {
        if($topic !== null){
            return $this->addAndFilter('Topic', $topic);
        }

        return $this;
    }

    public function byDate(): self
    {
        return $this->addOrder('Date');
    }

    public function getActiveVisitsWithTopic(Topic $topic = null): ExtendedCollection
    {
        return $this
            ->isActive()
            ->hasTopic($topic)
            ->afterToday()
            ->byDate()
            ->getMatching();
    }

    public function findTodaysVisits(): ExtendedCollection
    {
        return $this
            ->addAndFilter('Date', Carbon::today()->toDateString())
            ->getMatching();
    }

    public function getActiveVisitsAfterToday(): ExtendedCollection
    {
        $sortFunction = function(Visit $v1, Visit $v2){
            if ($v1->getDateString() === $v2->getDateString()) {
                return $v1->getTopic()->getId() - $v2->getTopic()->getId();
            }
            return $v1->getDate()->lt($v2->getDate()) ? -1 : 1;
        };

        return $this
            ->isActive()
            ->afterToday()
            ->getMatching()
            ->sortByFunction($sortFunction);
    }

    /* All active visits that are in the future, have the same topic and the same school, i.e. from the "parallelklass" */
    public function getSiblingVisits(Visit $visit): ExtendedCollection
    {
        $group = $visit->getGroup();

        if(!($group instanceof Group)){
            return ExtendedCollection::create();
        }

        $visits = $this
            ->isActive()
            ->hasTopic($visit->getTopic())
            ->afterToday()
            ->getMatching();

        return $visits
            ->filter(fn(Visit $v) => $v->getGroup()?->getSchool() === $group->getSchool());

    }



}