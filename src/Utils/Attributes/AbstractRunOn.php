<?php


namespace App\Utils\Attributes;


abstract class AbstractRunOn
{
    public function __construct(private ?string $method = null, private ?string $class = null)
    {
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function hasClass(): bool
    {
        return $this->getClass() !== null;
    }
}