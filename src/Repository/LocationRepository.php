<?php

namespace App\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

class LocationRepository extends EntityRepository
{
    use Filterable;

    public function getActiveLocations(): Collection
    {
        return $this->isActive()->getMatching();
    }

}