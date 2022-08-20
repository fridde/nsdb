<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use App\Utils\ExtendedCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: LocationRepository::class), ORM\Table(name: "locations")]
class Location
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column]
    protected string $Name;

    #[ORM\Column(nullable: true)]
    protected ?string $Coordinates;

    #[ORM\Column(nullable: true)]
    protected ?string $Description;

    #[ORM\Column]
    protected bool $Status = true;


    #[ORM\OneToMany(mappedBy: "Location", targetEntity: Topic::class)]
    protected Collection $Topics;


    public function __construct()
    {
        $this->Topics = new ExtendedCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }


    public function setId(int $id): void
    {
        $this->id = $id;
    }


    public function getName(): string
    {
        return $this->Name;
    }


    public function setName(string $Name): void
    {
        $this->Name = $Name;
    }


    public function getCoordinates(): ?string
    {
        return $this->Coordinates;
    }


    public function setCoordinates(?string $Coordinates): void
    {
        $this->Coordinates = $Coordinates;
    }


    public function getDescription(): ?string
    {
        return $this->Description;
    }


    public function setDescription(?string $Description): void
    {
        $this->Description = $Description;
    }


    public function isActive(): bool
    {
        return $this->Status;
    }


    public function setStatus(bool $Status): void
    {
        $this->Status = $Status;
    }


}