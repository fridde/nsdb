<?php

namespace App\Entity;

use App\Repository\CalendarEventRepository;
use App\Utils\Attributes\RunOnChange;
use App\Utils\Attributes\RunOnCreation;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[RunOnCreation]
#[ORM\Entity(repositoryClass: CalendarEventRepository::class)]
#[ORM\Table(name: "calendar_events")]
class CalendarEvent
{
    #[ORM\Id, ORM\Column(nullable: true), ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column]
    #[RunOnChange]
    protected string $StartDate;

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $EndDate = null;

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $Time = null;

    #[ORM\Column]
    #[RunOnChange]
    protected string $Title;

    #[ORM\Column]
    #[RunOnChange]
    protected bool $Status = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[RunOnChange]
    protected ?string $Description = null;

    #[ORM\Column(nullable: true)]
    #[RunOnChange]
    protected ?string $Location = null;

    public function getId(): int
    {
        return $this->id;
    }


    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): void
    {
        $this->Title = $Title;
    }

    public function getStatus(): bool
    {
        return $this->Status;
    }

    public function setStatus(bool $Status): void
    {
        $this->Status = (bool) $Status;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): void
    {
        $this->Description = $Description;
    }

    public function getLocation(): ?string
    {
        return $this->Location;
    }

    public function setLocation(?string $Location): void
    {
        $this->Location = $Location;
    }

    public function getStartDate(): string
    {
        return $this->StartDate;
    }

    public function setStartDate(string $StartDate): void
    {
        $this->StartDate = $StartDate;
    }

    public function getEndDate(): ?string
    {
        return $this->EndDate;
    }

    public function setEndDate(?string $EndDate): void
    {
        $this->EndDate = $EndDate;
    }

    public function hasEndDate(): bool
    {
        return !empty($this->getEndDate());
    }

    public function getTime(): ?string
    {
        return $this->Time;
    }

    public function setTime(?string $Time): void
    {
        $this->Time = $Time;
    }


}