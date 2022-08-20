<?php

namespace App\Repository;

use App\Entity\Record;
use App\Utils\ExtendedCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityRepository;

class RecordRepository extends EntityRepository
{
    use Filterable;

    public function hasType(string $type): self
    {
        return $this->addAndFilter('Type', $type);
    }

    public function byCreationDate(): self
    {
        return $this->addOrder('Created', Criteria::DESC);
    }

    public function getRecordsByContent(array $contentCriteria = []): ExtendedCollection
    {
        $records = $this->getMatching();

        foreach($contentCriteria as $key => $val){
            $val = (array) $val;
            $records = $records->filter(fn(Record $r) => in_array($r->getFromContent($key), $val, true));
        }
        return $records;
    }

    public function typeIsOneOf(array $potentialTypes): self
    {
        return $this->addAndFilter('Type', $potentialTypes, Comparison::IN);
    }

}
