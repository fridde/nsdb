<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\School;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

class GroupRepository extends EntityRepository
{
    use Filterable;

    public function hasSchool(School $school): self
    {
        return $this->addAndFilter('School', $school);
    }

    public function hasStartYear(int $startYear): self
    {
        return $this->addAndFilter('StartYear', $startYear);
    }

    public function hasSegment(string $segment): self
    {
        return $this->addAndFilter('Segment', $segment);
    }

    public function getActiveGroupsFromSegmentWithStartYear(string $segment, int $startYear): Collection
    {
        $sortFunction = fn(Group $g1, Group $g2) => $g1->getSchool()->getVisitOrder() - $g2->getSchool()->getVisitOrder();

        return $this
            ->isActive()
            ->hasSegment($segment)
            ->hasStartYear($startYear)
            ->getMatching()
            ->sortByFunction($sortFunction);
    }
}