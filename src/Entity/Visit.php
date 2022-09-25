<?php

namespace App\Entity;

use App\Repository\VisitRepository;
use App\Utils\Attributes\ConvertToEntityFirst;
use App\Utils\Attributes\RunOnChange;
use App\Utils\Attributes\RunOnCreation;
use App\Utils\Attributes\Serializable;
use App\Utils\ExtendedCollection;
use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[RunOnCreation]
#[ORM\Entity(repositoryClass: VisitRepository::class), ORM\Table(name: "visits")]
class Visit
{
    //use DefaultSerializable;

    //public array $standardMembers = ['id', 'Time', 'Status', 'BusIsBooked', 'FoodIsBooked'];

    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    #[Serializable]
    protected int $id;

    #[RunOnChange(isAssociative: true)]
    #[ORM\ManyToOne(inversedBy: "Visits")]
    protected ?Group $Group;

    #[ORM\Column]
    #[RunOnChange]
    protected string $Date;

    #[RunOnChange(isAssociative: true)]
    #[ORM\ManyToOne(inversedBy: "Visits")]
    protected Topic $Topic;

    /** This is the owning side. The visit has many colleagues (=users)
     */
    #[RunOnChange(isCollection: true)]
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: "Visits")]
    #[ORM\JoinTable(name: "colleagues_visits")]
    protected Collection $Colleagues;

    #[ORM\OneToMany(mappedBy: "Visit", targetEntity: Note::class)]
    protected Collection $Notes;

    #[RunOnChange]
    #[ORM\Column]
    protected bool $Confirmed = false;

    #[RunOnChange]
    #[ORM\Column(nullable: true)]
    protected ?string $Time;

    #[RunOnChange]
    #[ORM\Column]
    protected bool $Status = true;

    #[ORM\Column(type: Types::SMALLINT)]
    protected int $BusStatus = 0;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    protected ?int $Rating;

    public const BUS_IS_BOOKED = 1;
    public const BUS_IS_BOOKED_AND_CONFIRMED = 2;

    public function __construct()
    {
        $this->Notes = new ExtendedCollection();
        $this->Colleagues = new ExtendedCollection();
    }

    public function __toString(): string
    {
        $s = $this->getDateString() . ', ';
        $s .= $this->getTopic()->getShortName();
        if ($this->hasGroup()) {
            $s .= ', ' . mb_strtoupper($this->getGroup()?->getSchool()->getId());
            $s .= ': ' . $this->getGroup()?->getName();
        }
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


    public function getGroup(): ?Group
    {
        return $this->Group;
    }

    #[ConvertToEntityFirst]
    public function setGroup(?Group $Group): void
    {
        $this->Group = $Group;
    }

    public function hasGroup(): bool
    {
        return $this->getGroup() instanceof Group;
    }


    public function getDate(): Carbon
    {
        return Carbon::parse($this->Date);
    }


    public function setDate(\DateTime|string $Date): void
    {
        if ($Date instanceof \DateTime) {
            $Date = Carbon::instance($Date)->toDateString();
        }
        $this->Date = $Date;
    }

    public function getDateString(): string
    {
        return $this->getDate()->toDateString();
    }

    public function setDateString(string $Date): void
    {
        $this->setDate($Date);
    }


    public function getTopic(): Topic
    {
        return $this->Topic;
    }


    public function setTopic(Topic $Topic): void
    {
        $this->Topic = $Topic;
    }


    public function getColleagues(): Collection
    {
        return $this->Colleagues;
    }


    public function setColleagues(Collection $Colleagues): void
    {
        $this->Colleagues = $Colleagues;
    }

    public function addColleague(User $user): void
    {
        $this->Colleagues->add($user);
    }

    public function removeColleague(User $user): void
    {
        $this->Colleagues->removeElement($user);
    }


    public function getNotes(): ExtendedCollection
    {
        return ExtendedCollection::create($this->Notes);
    }


    public function setNotes(Collection $Notes): void
    {
        $this->Notes = $Notes;
    }


    public function isConfirmed(): bool
    {
        return $this->Confirmed;
    }


    public function setConfirmed(bool $Confirmed): void
    {
        $this->Confirmed = $Confirmed;
    }


    public function getTime(): ?string
    {
        return $this->Time;
    }

    public function hasTime(): bool
    {
        return !empty($this->getTime());
    }

    public function setTime(?string $Time): void
    {
        $this->Time = $Time;
    }


    public function getStatus(): bool
    {
        return (bool) $this->Status;
    }


    public function setStatus(int|bool $Status): void
    {
        $this->Status = (bool)$Status;
    }

    public function isActive(): bool
    {
        return $this->Status;
    }

    public function getBusStatus(): int
    {
        return $this->BusStatus;
    }

    public function setBusStatus(int|bool $BusStatus): void
    {
        $this->BusStatus = (int)$BusStatus;
    }

    public function BusIsAtLeastBooked(): bool
    {
        return $this->BusStatus !== 0;
    }

    public function needsBus(): bool
    {
        if (!$this->hasGroup()) {
            return false;
        }
        return $this->getGroup()?->getSchool()->needsBus($this->getTopic()->getLocation());
    }

    public function needsFood(): bool
    {
        if (!$this->hasGroup()) {
            return false;
        }
        return !empty($this->getTopic()->getFood());
    }


    public function getRating(): ?int
    {
        return $this->Rating;
    }


    public function setRating(int $Rating): void
    {
        $this->Rating = $Rating;
    }

    public function hasRating(): bool
    {
        return $this->Rating !== null;
    }

    public function isAfter(Carbon $date): bool
    {
        return $date->lte($this->getDate());
    }

    public function isBefore(Carbon $futureDate): bool
    {
        return $futureDate->gt($this->getDate());
    }

    public function isAfterToday(): bool
    {
        return $this->isAfter(Carbon::today());
    }

    /**
     * @return int Returns 0 if dates are equal, +1 if argument is later than this visit, otherwise -1
     */
    public function compareDateWithVisit(Visit $visit): int
    {
        $d1 = $this->getDate();
        $d2 = $visit->getDate();

        if($d1->eq($d2)){
            return 0;
        }

        return $d1->lte($d2) ? -1 : 1;
    }


    public function getLabel(): string
    {
        $vars = [$this->getTopic()->getShortName()];
        $txt = '%s (Reservtillfälle)';

        if ($this->hasGroup()) {
            $txt = '%s med %s från %s (%s)';
            $vars = array_merge($vars, [
                $this->getGroup()?->getName(),
                $this->getGroup()?->getSchool()->getName(),
                $this->getGroup()?->getUser()?->getFullName()
            ]);
        }

        return sprintf($txt, ...$vars);
    }

}