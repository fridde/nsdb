<?php

namespace App\Entity;

use App\Repository\RecordRepository;
use Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecordRepository::class), ORM\Table(name: "recordings")]
class Record
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column]
    protected string $Type;

    #[ORM\Column(type: Types::JSON)]
    protected array $Content = [];

    #[ORM\Column]
    protected DateTime $Created;

    public function __construct(string $type = null)
    {
        if(null !== $type){
            $this->setType($type);
        }
        $this->Created = Carbon::now();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->Type;
    }

    public function isType(string $type): bool
    {
        return $this->getType() === $type;
    }

    public function setType(string $Type): void
    {
        $this->Type = $Type;
    }

    public function getContent(): array
    {
        return $this->Content;
    }

    public function getFromContent(string $key)
    {
        return $this->getContent()[$key] ?? null;
    }

    public function setContent(array $Content): void
    {
        $this->Content = $Content;
    }

    public function hasInContent(...$args): bool
    {
        $args = match(count($args)) {
            1 => $args[0],
            2 => [$args[0] => $args[1]],
            default => null
        };
        if($args === null){
            throw new \InvalidArgumentException('This method accepts either 1 or 2 arguments');
        }

        foreach($args as $field => $val){
            if($this->getContent()[$field] !== $val){
                return false;
            }
        }
        return true;
    }

    public function addToContent(string $key, $value): void
    {
        $content = $this->getContent();
        $content[$key] = $value;
        $this->setContent($content);
    }

    public function getCreated(): DateTime|Carbon
    {
        return $this->Created;
    }


    public function setCreated(DateTime|Carbon $Created): void
    {
        $this->Created = $Created;
    }

    public function wasCreatedAfter(Carbon $date): bool
    {
        return $this->getCreated()->gt($date);
    }

    public function __serialize(): array
    {
        return [
            'Type' => $this->getType(),
            'Content' => $this->getContent(),
            'Created' => $this->getCreated()->toIso8601String()
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->setType($data['Type']);
        $this->setContent($data['Content']);
        $this->setCreated(Carbon::create($data['Created']));
    }

}