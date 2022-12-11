<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\School;
use App\Enums\Segment;
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

    public function hasSegment(Segment $segment): self
    {
        return $this->addAndFilter('Segment', $segment->value);
    }

    public function getActiveGroupsFromSegmentWithStartYear(Segment $segment, int $startYear): Collection
    {
        $sortFunction = fn(Group $g1, Group $g2) => $g1->getSchool()->getVisitOrder() - $g2->getSchool()->getVisitOrder();

        return $this
            ->isActive()
            ->hasSegment($segment)
            ->hasStartYear($startYear)
            ->getMatching()
            ->sortByFunction($sortFunction);
    }

    /* A group that is equal in school, segment and startyear, swedish "parallellklass" */
    public function getActiveSiblingGroups(Group $group): Collection
    {
        return $this
            ->hasStartYear($group->getStartYear())
            ->hasSegment($group->getSegment())
            ->hasSchool($group->getSchool())
            ->isActive()
            ->getMatching();
    }
}