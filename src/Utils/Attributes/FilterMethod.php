<?php


namespace App\Utils\Attributes;

#[\Attribute]
class FilterMethod
{
    private string $methodName;

    public function __construct(private ?string $jsonKey = null)
    {
    }

    public function setMethodName(string $methodName): void
    {
        $this->methodName = $methodName;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getJsonKey(): string
    {
        return $this->jsonKey ?? $this->methodName;
    }
}