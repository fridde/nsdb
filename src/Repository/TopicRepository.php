<?php

namespace App\Repository;

use App\Entity\Topic;
use App\Utils\ExtendedCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Comparison;

class TopicRepository extends EntityRepository
{
    use Filterable;

    public function getActiveTopics(): ExtendedCollection
    {
        return $this->isActive()->getMatching();
    }

    public function getTopicBySymbol(string $symbol): ?Topic
    {
        $topic = $this->isActive()
            ->addAndFilter('Symbol', $symbol)
            ->getMatching()->first();

        return $topic === false ? null : $topic ;
    }

    public function hasSymbol(): self
    {
        return $this->addAndFilter('Symbol', null, Comparison::NEQ);
    }

}