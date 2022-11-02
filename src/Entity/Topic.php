<?php

namespace App\Entity;

use App\Repository\TopicRepository;
use App\Utils\ExtendedCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: TopicRepository::class), ORM\Table(name: "topics")]
class Topic
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(nullable: true)]
    protected ?string $Segment;

    #[ORM\Column(type: Types::SMALLINT)]
    protected int $VisitOrder = -1;

    #[ORM\Column]
    protected string $ShortName;

    #[ORM\Column(nullable: true)]
    protected ?string $LongName;

    #[ORM\Column(nullable: true)]
    protected ?string $Symbol;

    #[ORM\Column(nullable: true)]
    protected ?float $ColleaguesPerGroup;

    #[ORM\ManyToOne(inversedBy: "Topics")]
    protected Location $Location;

    #[ORM\Column(nullable: true)]
    protected ?string $Food;

    #[ORM\Column(nullable: true)]
    protected ?string $Url;

    #[ORM\Column]
    protected bool $Status = true;

    #[ORM\OneToMany(mappedBy: "Topic", targetEntity: Visit::class)]
    #[ORM\OrderBy(["Date" => "ASC"])]
    protected Collection $Visits;

    public function __construct()
    {
        $this->Visits = new ExtendedCollection();
    }

    public function __toString()
    {
        $s = '[' . $this->getSegment();
        $s .= ':' . $this->getVisitOrder();
        $s .= '] ' . $this->getShortName();

        return  $s;
    }



    public function getId(): int
    {
        return $this->id;
    }


    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSegment(): ?string
    {
        return $this->Segment;
    }


    public function setSegment(?string $Segment): void
    {
        $this->Segment = $Segment;
    }


    public function getVisitOrder(): int
    {
        return $this->VisitOrder;
    }


    public function setVisitOrder(int $VisitOrder): void
    {
        $this->VisitOrder = $VisitOrder;
    }


    public function getShortName(): string
    {
        return $this->ShortName;
    }


    public function setShortName(string $ShortName): void
    {
        $this->ShortName = $ShortName;
    }


    public function getLongName(): ?string
    {
        return $this->LongName;
    }


    public function setLongName(?string $LongName): void
    {
        $this->LongName = $LongName;
    }

    public function getLongestName(): ?string
    {
        return $this->getLongName() ?? $this->getShortName();
    }

    public function getSymbol(): ?string
    {
        return $this->Symbol;
    }


    public function setSymbol(?string $Symbol): void
    {
        $this->Symbol = $Symbol;
    }

    public function hasSymbol(): bool
    {
        return $this->Symbol !== null;
    }

    public function getColleaguesPerGroup(): ?float
    {
        return $this->ColleaguesPerGroup;
    }


    public function setColleaguesPerGroup(float $ColleaguesPerGroup = null): void
    {

        $this->ColleaguesPerGroup = $ColleaguesPerGroup;
    }

    public function getLocation(): Location
    {
        return $this->Location;
    }


    public function setLocation(Location $Location): void
    {
        $this->Location = $Location;
    }


    public function getFood(): ?string
    {
        return $this->Food;
    }


    public function setFood(?string $Food = null): void
    {
        $this->Food = $Food;
    }


    public function getUrl(): ?string
    {
        return $this->Url;
    }


    public function setUrl(?string $Url): void
    {
        $this->Url = $Url;
    }


    public function isActive(): bool
    {
        return $this->Status;
    }

    public function getStatus(): bool
    {
        return $this->Status;
    }


    public function setStatus(bool $Status): void
    {
        $this->Status = $Status;
    }

    public function getVisits(): Collection
    {
        return $this->Visits;
    }

    public function getFutureVisits(): Collection
    {
        return $this->getVisits()->filter(fn(Visit $v) => $v->isAfterToday());
    }

    public function hasFutureVisits(): bool
    {
        return ! $this->getFutureVisits()->isEmpty();
    }





}