<?php

namespace App\Utils;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ExtendedCollection extends ArrayCollection
{

    public function __construct(array|Collection $elements = null)
    {
        if ($elements === null) {
            $elements = [];
        } elseif ($elements instanceof Collection) {
            $elements = $elements->toArray();
        }

        parent::__construct($elements);
    }

    public static function create(array|Collection $elements = null): self
    {
        return new self($elements);
    }

    public static function fromJsonString(string $jsonString = '{}'): self
    {
        return new self(json_decode($jsonString, true));
    }

    public function sortByFunction(Closure $closure): self
    {
        $elements = $this->toArray();
        usort($elements, $closure);

        return $this->createFrom($elements);
    }

    public function attach(ExtendedCollection $collection): self
    {
        return $this->createFrom(array_merge($this->toArray(), $collection->toArray()));
    }

    public function attachTo(int|string $key, ExtendedCollection $collection): self
    {
        $innerArray = $this->get($key);
        if (!($innerArray instanceof self)) {
            throw new \TypeError('The value at index ' . $key . ' must by of type ' . __CLASS__);
        }
        $this->set($key, $innerArray->attach($collection));

        return $this;
    }

    public function attachArray(array $array = null): self
    {
        $array ??= [];

        return $this->attach($this->createFrom($array));
    }

    /* Flattens a collection of collections or arrays into a single collections.
        Works to the depth of one level
     * */
    public function collapse(): self
    {
        $results = [];

        foreach ($this->toArray() as $item) {
            if ($item instanceof self) {
                $item = $item->toArray();
            }
            $results[] = (array)$item;
        }

        return $this->createFrom(array_merge([], ...$results));
    }

    public function first(): mixed
    {
        $value = parent::first();

        return $value === false ? null : $value;
    }

    public function removeNull(): self
    {
        return $this->filter(fn($v) => $v !== null);
    }

    public function unique(): self
    {
        return $this->createFrom(array_unique($this->toArray(), SORT_REGULAR));
    }

    public function reverse(): self
    {
        return $this->createFrom(array_reverse($this->toArray()));
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function doesNotContain($value): bool
    {
        return !$this->contains($value);
    }

    public function getAsCollection(string $key): self
    {
        return $this->createFrom((array)$this->get($key));
    }

    public function implode(string $glue = ','): string
    {
        return implode($glue, $this->toArray());
    }

    public function withKey(int|string|callable $keyFunction): self
    {
        $fn = (is_callable($keyFunction) ? $keyFunction : fn($v) => $v[$keyFunction]);
        $keys = $this->map($fn)->toArray();

        return $this->createFrom(array_combine($keys, $this->toArray()));
    }

    public function search(mixed $value): int|string|null
    {
        $key = array_search($value, $this->toArray(), true);

        return $key === false ? null : $key;
    }

    public function searchAll(mixed $value): ExtendedCollection
    {
        $keys = array_keys($this->toArray(), $value, true);

        return $this->createFrom($keys);
    }

    public function walk(callable $callback, mixed $arg = null): self
    {
        $array = $this->toArray();
        array_walk($array, $callback, $arg);

        return $this->createFrom($array);
    }

}