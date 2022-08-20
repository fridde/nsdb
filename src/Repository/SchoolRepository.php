<?php

namespace App\Repository;

use App\Entity\School;
use App\Utils\ExtendedCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

class SchoolRepository extends EntityRepository
{
    use Filterable;

    public function getActiveSchools(): Collection
    {
        $this->isActive();
        return $this->getMatching();
    }

    public function getActiveSchoolsByVisitOrder(): ExtendedCollection
    {
        $sortFunction = fn(School $s1, School $s2) => $s1->getVisitOrder() - $s2->getVisitOrder();

        return $this->getActiveSchools()->sortByFunction($sortFunction);
    }

}