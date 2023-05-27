<?php

namespace App\Repository;

use App\Utils\ExtendedCollection;
use Doctrine\ORM\EntityRepository;

class LocationRepository extends EntityRepository
{
    use Filterable;

    public function getActiveLocations(): ExtendedCollection
    {
        return $this->isActive()->getMatching();
    }

}