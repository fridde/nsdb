<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class CalendarEventRepository extends EntityRepository
{
    use Filterable;

}