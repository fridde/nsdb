<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use App\Utils\Attributes\ConvertToEntityFirst;
use App\Utils\Attributes\RunOnChange;
use App\Utils\ExtendedCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class), ORM\Table(name: "groups")]
class Group
{
    //use DefaultSerializable;

    //public array $standardMembers = ['id', 'Name', 'Segment', 'StartYear', 'NumberStudents', 'Info'];

    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $Name;

    #[ORM\ManyToOne(inversedBy: "Groups")]
    #[RunOnChange(isAssociative: true)]
    protected ?User $User;

    #[ORM\ManyToOne(inversedBy: "Groups")]
    protected School $School;

    #[ORM\Column(nullable: true)]
    protected ?string $Segment;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    protected ?int $StartYear;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[RunOnChange]
    protected ?int $NumberStudents;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[RunOnChange]
    protected ?string $Info;

    #[ORM\Column]
    #[RunOnChange]
    protected bool $Status = true;

    #[ORM\OneToMany(mappedBy: "Group", targetEntity: Visit::class)]
    #[ORM\OrderBy(['Date' => 'ASC'])]
    protected Collection $Visits;

    public function __construct()
    {
        $this->Visits = new ExtendedCollection();
    }

    public function __toString(): string
    {
        $s = '[' . strtoupper($this->getSchool()->getId()) . ':';
        $s .= $this->getSegment() . '] ' . $this->getName();

        return $s;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }


    public function getName(): ?string
    {
        return $this->Name;
    }


    public function setName(?string $Name): void
    {
        $this->Name = $Name;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    #[ConvertToEntityFirst]
    public function setUser(User $User = null): void
    {
        $this->User = $User;
    }

    public function hasUser(): bool
    {
        return $this->getUser() !== null;
    }

    public function getSchool(): School
    {
        return $this->School;
    }


    #[ConvertToEntityFirst]
    public function setSchool(School $School): void
    {
        $this->School = $School;
    }

    public function getSegment(): ?string
    {
        return $this->Segment;
    }

    public function setSegment(?string $Segment): void
    {
        $this->Segment = $Segment;
    }

    public function isSegment(string $segment): bool
    {
        return $this->getSegment() === $segment;
    }

    public function getStartYear(): ?int
    {
        return $this->StartYear;
    }

    public function setStartYear(?int $StartYear): void
    {
        $this->StartYear = $StartYear;
    }

    public function hasStartYear(int $StartYear): bool
    {
        return $this->getStartYear() === $StartYear;
    }

    public function getNumberStudents(): ?int
    {
        return $this->NumberStudents;
    }

    public function setNumberStudents(?int $NumberStudents): void
    {
        $this->NumberStudents = $NumberStudents;
    }

    public function getInfo(): ?string
    {
        return $this->Info;
    }

    public function setInfo(?string $Info): void
    {
        $this->Info = $Info;
    }

    public function isActive(): bool
    {
        return $this->getStatus();
    }

    public function getStatus(): bool
    {
        return $this->Status;
    }

    public function setStatus(bool $Status): void
    {
        $this->Status = $Status;
    }

    public function getLabel(): string
    {
        return (string)$this;
    }

    public function getVisits(): ExtendedCollection
    {
        return ExtendedCollection::create($this->Visits);
    }

    public function getNotes(): ExtendedCollection
    {


        return $this->getVisits()
            ->map(fn(Visit $v) => $v->getNotes())
            ->collapse();
    }

    public function hasFutureVisit(): bool
    {
        return $this->getNextVisit() !== null;
    }

    public function getFutureVisits(): ExtendedCollection
    {
        return $this->getVisits()->filter(
            fn(Visit $v) => $v->isAfterToday() && $v->isActive()
        );
    }

    public function getNextVisit(): ?Visit
    {
        return $this->getFutureVisits()->first();
    }

}