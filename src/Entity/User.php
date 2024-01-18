<?php

namespace App\Entity;

use App\Enums\Segment;
use App\Repository\UserRepository;
use App\Security\Role;
use App\Utils\Attributes\ConvertToEntityFirst;
use App\Utils\Attributes\RunOnChange;
use App\Utils\Attributes\RunOnCreation;
use App\Utils\ExtendedCollection;
use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: UserRepository::class), ORM\Table(name: "users")]
#[RunOnCreation]
class User implements UserInterface
{
    //use DefaultSerializable;

    // public array $standardMembers = ['id', 'FirstName', 'LastName', 'Mobil', 'Mail', 'Acronym'];

    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $FirstName;

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $LastName;

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $Mobil;

    #[ORM\Column(unique: true)]
    #[RunOnChange]
    protected string $Mail;

    #[ORM\ManyToOne(inversedBy: "Users")]
    protected School $School;

    #[ORM\Column(type: Types::JSON)]
    protected array $Roles = [];

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $Acronym;

    #[ORM\Column]
    #[RunOnChange]
    protected bool $Status = true;  // means active

    #[ORM\Column]
    protected \DateTime $Created;

    #[ORM\OneToMany(mappedBy: "User", targetEntity: Group::class)]
    #[ORM\OrderBy(['Name' => 'ASC'])]
    protected Collection $Groups;

    #[ORM\ManyToMany(targetEntity: Visit::class, mappedBy: "Colleagues")]
    protected Collection $Visits;

    #[ORM\OneToMany(mappedBy: "User", targetEntity: Note::class)]
    protected Collection $Notes;

    #[ORM\OneToMany(mappedBy: "User", targetEntity: Record::class)]
    protected Collection $Records;

    public function __construct()
    {
        $this->setCreated(Carbon::now());

        $this->Visits = new ExtendedCollection();
        $this->Groups = new ExtendedCollection();
        $this->Notes = new ExtendedCollection();
        $this->Records = new ExtendedCollection();
    }

    public function __toString(): string
    {
        return $this->getFullName().' ['. mb_strtoupper($this->getSchoolId()).']';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFirstName(): ?string
    {
        return $this->FirstName;
    }

    public function setFirstName(?string $FirstName): void
    {
        $this->FirstName = $FirstName;
    }

    public function getLastName(): ?string
    {
        return $this->LastName;
    }

    public function setLastName(?string $LastName): void
    {
        $this->LastName = $LastName;
    }

    public function getFullName(): string
    {
        return $this->FirstName . ' ' . $this->LastName;
    }

    public function getMobilInPlusFormat(): ?string
    {
        $nr = $this->getMobil();
        if(empty($nr) || str_starts_with($nr, '+46')){
            return $nr;
        }
        $nr = ltrim(filter_var($nr, FILTER_SANITIZE_NUMBER_INT), '0');
        return '+46' . str_replace('-', '', $nr);
    }

    public function getMobil(): ?string
    {
        return $this->Mobil;
    }

    public function setMobil(?string $Mobil): void
    {
        $this->Mobil = $Mobil;
    }

    public function hasMobil(): bool
    {
        return !empty($this->getMobil());
    }

    public function getMail(): ?string
    {
        return $this->Mail;
    }

    public function setMail(?string $Mail): void
    {
        $this->Mail = mb_strtolower(trim($Mail));
    }

    public function getSchool(): School
    {
        return $this->School;
    }

    public function getSchoolId(): string
    {
        return $this->getSchool()->getId();
    }

    #[ConvertToEntityFirst]
    public function setSchool(School $School): void
    {
        $this->School = $School;
    }

    public function hasSameSchoolAs(User $user): bool
    {
        return $this->getSchool()->equals($user->getSchool());
    }

    public function addRole(string $role): void
    {
        $this->Roles = Role::addRole($role, $this->getRoles());
    }

    public function addRoles(array $roles): void
    {
        $this->Roles = Role::addRoles($roles, $this->getRoles());
    }

    public function removeRole(string $role): void
    {
        $this->Roles = Role::removeRole($role, $this->getRoles());
    }

    public function removeRoles(array $roles): void
    {
        $this->Roles =  Role::removeRoles($roles, $this->getRoles());
    }

    public function getRoles(): array
    {
        return Role::addRole(Role::USER, $this->Roles);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function hasAtLeastRole(string $role): bool
    {
        $highest = Role::getHighestRole($this->getRoles());

        return Role::isAtLeast($highest, $role);
    }

    public function isPending(): bool
    {
        return ! $this->hasAtLeastRole(Role::ACTIVE_USER);
    }

    public function getAcronym(): ?string
    {
        return $this->Acronym;
    }

    public function setAcronym(?string $Acronym): void
    {
        $this->Acronym = $Acronym;
    }

    public function getFilteredAcronym(): ?string
    {
        if(str_contains($this->Acronym, ':')){
            return explode(':', $this->Acronym)[1];
        }
        return $this->Acronym;
    }

    public function getStatus(): bool
    {
        return $this->Status;
    }

    public function setStatus(int|bool $Status): void
    {
        $this->Status = (bool)$Status;
    }

    public function isActive(): bool
    {
        return $this->Status;
    }

    public function getCreated(): Carbon
    {
        return Carbon::instance($this->Created);
    }

    public function getGroups(): ExtendedCollection
    {
        return ExtendedCollection::create($this->Groups);
    }

    public function getRecords(): ExtendedCollection
    {
        return ExtendedCollection::create($this->Records);
    }

    public function getSegment(): ?Segment
    {
        /** @var Group $group  */
        $group = $this->getGroups()->first();

        return $group?->getSegment();
    }

    public function setCreated(\DateTime $Created): void
    {
        $this->Created = $Created;
    }

    public function hasGroupWithFutureVisit(): bool
    {
        return $this->getGroups()
            ->filter(fn(Group $g) => $g->hasFutureVisit())
            ->isNotEmpty();
    }

    public function hasActiveGroupInSegment(Segment $segment): bool
    {
        return $this->getGroups()
            ->filter(fn(Group $g) => $g->isActive() && $g->isSegment($segment))
            ->isNotEmpty();
    }

    public function getNextVisit(): ?Visit
    {
        return $this->getGroups()
            ->map(fn(Group $g) => $g->getNextVisit())
            ->removeNull()
            ->sortByFunction(fn(Visit $v1, Visit $v2) => $v1->compareDateWithVisit($v2))
            ->first();
    }

    public function nextVisitIsBefore(Carbon $date): bool
    {
        return $this->getNextVisit()?->getDate()->lt($date) ?? false;
    }

    public function getAllVisits(): ExtendedCollection
    {
        return $this->getGroups()
            ->map(fn(Group $g) => $g->getVisits())
            ->collapse();
    }

    public function getFutureVisits(): ExtendedCollection
    {
        return $this->getGroups()
            ->map(fn(Group $g) => $g->getFutureVisits())
            ->collapse()
            ->sortByFunction(fn(Visit $v1, Visit $v2) => $v1->getDate()->lt($v2->getDate()) ? -1 : 1);
    }

    public function getFutureVisitsUntil(Carbon $until): ExtendedCollection
    {
        return $this->getFutureVisits()->filter(fn(Visit $v) => $v->getDate()->lte($until));
    }

    public function getUnconfirmedFutureVisitsUntil(Carbon $until): ExtendedCollection
    {
        return $this->getFutureVisitsUntil($until)->filter(fn(Visit $v) => !$v->isConfirmed());
    }

    /** Code below is necessary to implement \Symfony\Component\Security\Core\User\UserInterface
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
      vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
     */

    public function getUserIdentifier(): string
    {
        return $this->getMail();
    }

    public function getUsername(): ?string
    {
        return $this->getMail();
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }



}