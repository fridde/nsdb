<?php


namespace App\Utils\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class RunOnChange extends AbstractRunOn
{
    public function __construct(
        ?string $method = null,
        ?string $class = null,
        public ?bool $isAssociative = null,
        public ?bool $isCollection = null
    )
    {
        parent::__construct($method, $class);
        if($isCollection){
            $this->isAssociative = true;
        }
    }
}
