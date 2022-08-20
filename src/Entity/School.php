<?php

namespace App\Entity;

use App\Repository\SchoolRepository;
use App\Utils\Attributes\ConvertToEntityFirst;
use App\Utils\ExtendedCollection;
use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: SchoolRepository::class), ORM\Table(name: "schools")]
class School
{
    //public array $standard_members = ['id', 'Name', 'Status', 'BusRule', 'FoodRule', 'Status'];

    public const NATURSKOLAN = 'natu';

    #[ORM\Id, ORM\Column]
    protected string $id;

    #[ORM\Column]
    protected string $Name;

    #[ORM\Column(nullable: true)]
    protected ?string $Coordinates;

    #[ORM\Column(type: Types::SMALLINT)]
    protected int $VisitOrder;

    #[ORM\Column(options: ["default" => 0])]
    protected int $BusRule = 0;

    #[ORM\Column]
    protected bool $Status = true;


    #[ORM\OneToMany(mappedBy: "School", targetEntity: Group::class)]
    #[ORM\OrderBy(["Name" => "ASC"])]
    protected Collection $Groups;


    #[ORM\OneToMany(mappedBy: "School", targetEntity: User::class)]
    #[ORM\OrderBy(["FirstName" => "ASC"])]
    protected Collection $Users;


    public function __construct()
    {
        $this->Groups = new ExtendedCollection();
        $this->Users = new ExtendedCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function equals(School $school): bool
    {
        return $this->getId() === $school->getId();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->Name;
    }

    public function setName($Name): void
    {
        $this->Name = $Name;
    }

    public function getCoordinates(): string
    {
        return $this->Coordinates;
    }

    public function setCoordinates(?string $Coordinates): void
    {
        $this->Coordinates = $Coordinates;
    }

    public function getVisitOrder(): int
    {
        return $this->VisitOrder;
    }

    public function setVisitOrder(int $VisitOrder): void
    {
        $this->VisitOrder = $VisitOrder;
    }

    public function getBusRule(): int
    {
        return $this->BusRule;
    }

    public function setBusRule(int $BusRule): void
    {
        $this->BusRule = $BusRule;
    }

    public function addLocationToBusRule(Location $location): void
    {
        $locationBusValue = 1 << $location->getId();  // 2^id

        $this->setBusRule($this->getBusRule() | $locationBusValue);
    }

    public function removeLocationFromBusRule(Location $location): void
    {
        $locationBusValue = 1 << $location->getId(); // 2^id

        $this->setBusRule($this->getBusRule() ^ $locationBusValue); // removes the corresponding bit
    }

    public function updateBusRule(Location $location, bool $needsBus): void
    {
        if ($needsBus) {
            $this->addLocationToBusRule($location);
        } else {
            $this->removeLocationFromBusRule($location);
        }
    }

    public function needsBus(Location $location = null): bool
    {
        if ($location === null) {
            return false;
        }
        $locationBusValue = 1 << $location->getId();

        return $this->getBusRule() & $locationBusValue; // bitwise
    }

    public function getStatus(): bool
    {
        return $this->Status;
    }

    public function setStatus(int|bool $status): void
    {
        $this->Status = (bool)$status;
    }

    public function getGroups(): Collection
    {
        return $this->Groups;
    }

    public function getGroupsByName(): Collection
    {
        return $this->getGroups();

    }


    public function getUsers(): Collection
    {
        return $this->Users;
    }

    #[ConvertToEntityFirst]
    public function addUser(User $User): void
    {
        $this->Users->add($User);
    }

    /**
     * @param mixed $startYear If null, the current year is assumed. If false, all years are included
     */
    public function getActiveGroupsBySegmentAndYear(string $segment, bool|int $startYear = null): Collection
    {
        $startYear = $startYear ?? Carbon::today()->year;

        return $this->getGroups()->filter(fn(Group $g) =>
            ($startYear === false || $g->hasStartYear($startYear))
            && ($g->isSegment($segment))
            && ($g->isActive())
        );
    }

    public function setGroups(Collection|ExtendedCollection $Groups): void
    {
        $this->Groups = $Groups;
    }

    public function getSegments(): ExtendedCollection
    {
        return ExtendedCollection::create(
            $this->getGroups()
                ->filter(fn(Group $g) => $g->isActive())
                ->map(fn(Group $g) => $g->getSegment()))
            ->unique();
    }

    public function hasSegment(string $segmentId): bool
    {
        return ! $this->getActiveGroupsBySegmentAndYear($segmentId, false)->isEmpty();
    }

    public function getNrActiveGroupsBySegmentAndYear(string $segmentId, $startYear = null): int
    {
        return $this->getActiveGroupsBySegmentAndYear($segmentId, $startYear)->count();
    }

//    public function isNaturskolan(): bool
//    {
//        return $this->getId() === self::NATURSKOLAN;
//    }


}