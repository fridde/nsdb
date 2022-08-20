<?php


namespace App;


class Settings
{
    private array $settings;

    public function __construct(array $appSettings = [])
    {
        $this->settings = $appSettings;
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
}