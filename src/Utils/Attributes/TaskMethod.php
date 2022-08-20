<?php


namespace App\Utils\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class TaskMethod
{
    public function __construct(
        private ?string $name = null
    )
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function hasName(string $name): bool
    {
        return $this->getName() === $name;
    }


}