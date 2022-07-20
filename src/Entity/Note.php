<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use App\Utils\Attributes\ConvertToEntityFirst;
use App\Utils\Attributes\RunOnChange;
use App\Utils\Attributes\RunOnCreation;
use Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[RunOnCreation]
#[ORM\Entity(repositoryClass: NoteRepository::class), ORM\Table(name: "notes")]
class Note
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    protected int $id;

    #[ORM\ManyToOne(inversedBy: "Notes")]
    protected Visit $Visit;

    #[ORM\ManyToOne(inversedBy: "Notes")]
    protected User $User;

    #[ORM\Column(type: Types::TEXT)]
    #[RunOnChange]
    protected string $Text;

    // TODO: Implement GEDMO timestampable (on=update)
    #[ORM\Column(nullable: true)]
    protected ?DateTime $LastUpdate;

    public function __construct()
    {
        $this->setLastUpdate(Carbon::now());
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getVisit(): Visit
    {
        return $this->Visit;
    }

    #[ConvertToEntityFirst]
    public function setVisit(Visit $Visit): void
    {
        $this->Visit = $Visit;
    }

    public function getUser(): User
    {
        return $this->User;
    }

    #[ConvertToEntityFirst]
     public function setUser(UserInterface|User $User): void
    {
        $this->User = $User;
    }

    public function getText(): string
    {
        return $this->Text;
    }

    public function setText(string $Text): void
    {
        $this->Text = $Text;
    }

    public function getLastUpdate(): ?DateTime
    {
        return $this->LastUpdate;
    }

     public function setLastUpdate(?DateTime $LastUpdate): void
    {
        $this->LastUpdate = $LastUpdate;
    }


}