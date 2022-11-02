<?php


namespace App;


class Settings
{
    private array $settings;
    private const CHANGEABLES = 'changeables';

    public function __construct(array $appSettings = [], array $changeableSettings = [])
    {
        $this->settings = $appSettings;
        $this->settings['changeables'] = $changeableSettings;
    }

    public function get(string $index): mixed
    {
        $keys = explode('.', $index);

        return $this->getUsingKeys(...$keys);
    }

    public function getUsingKeys(...$keys): mixed
    {
        $return = $this->settings;
        foreach($keys as $key){
            $return = $return[$key];
        }

        return $return;
    }

    public function getChangeable(string $index): mixed
    {
        $index = self::CHANGEABLES . '.' . $index;

        return $this->get($index);
    }
}