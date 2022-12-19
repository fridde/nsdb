<?php /** @noinspection MissingService */


namespace App;


use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class Settings
{
    private array $settings;
    private const APP_SETTINGS = 'app_settings';
    private const EDITABLE_SETTINGS = 'editable_settings';

    public function __construct(
        private readonly ContainerBagInterface $params,
        private readonly KernelInterface $kernel
    )
    {
        $this->settings = $this->params->get(self::APP_SETTINGS);
        $this->settings += $this->params->get(self::EDITABLE_SETTINGS);
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
            $return = $return[$key] ?? null;
            if($return === null){
                return null;
            }
        }

        return $return;
    }

    public function save(string $key, $value): void
    {
        $this->settings[$key] = $value;

        $path = $this->getProjectDir() . '/config/editable_settings.yaml';
        $values = Yaml::parseFile($path);
        $values['parameters'][self::EDITABLE_SETTINGS][$key] = $value;

        file_put_contents($path, Yaml::dump($values, 99));
    }

    public function getProjectDir(): string
    {
        return $this->kernel->getProjectDir();
    }
}