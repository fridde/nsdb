<?php


namespace App\Security\Key;


use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiKeyManager
{
    public const SEPARATOR = '-';

/*    private array $key = [
        'type' => null,
        'id' => null,
        'salt' => null,
        'version' => null,
        'hash' => null
    ];
*/

    public function __construct(
        private RequestStack $requestStack,
        private string $cronKeyVersion,
        private string $appSecret
    )
    {
    }

    public function createKeyFromGivenString(string $givenString = null): Key
    {
        $key = new Key();
        if (!empty($givenString)) {
            [$key->type, $key->id, $key->salt, $key->hash] = explode(Key::SEPARATOR, $givenString);
            $key->version = $this->getVersionForKey($key);
        }

        return $key;
    }

    public function createKeyFromValues(string $type, string $id = '', string $version = null, string $salt = null): Key
    {
        $key = Key::create()->setType($type)->setId($id);
        $key->version = $version ?? $this->getVersionForKey($key);
        $key->setSalt($salt);

        return $key;
    }

    public function createCodeStringForKey(Key $key): string
    {
        $key->hash = $this->calculateHashForKey($key);
        $clearText = implode(Key::SEPARATOR, [$key->type, $key->id, $key->salt]);

        return $clearText . Key::SEPARATOR . $key->hash;
    }

    public function calculateHashForKey(Key $key): string
    {
        $hashables = [$key->type, $key->id, $key->salt, $key->version, $this->appSecret];
        $hash = self::createHashFromString(implode(Key::SEPARATOR, $hashables), $key->getHashLength());
        return self::createHashFromString(implode(Key::SEPARATOR, $hashables), $key->getHashLength());
    }

    public function isValidKey(Key $key): bool
    {
        return $this->calculateHashForKey($key) === $key->hash;
    }


    private function getVersionForKey(Key $key): string
    {
        return (string)match ($key->type) {
            Key::TYPE_ANON => Key::getDayFiveHoursAgo(),
            Key::TYPE_COOKIE, Key::TYPE_URL => $key->getHalfAYearAgo(),
            Key::TYPE_CRON => $this->cronKeyVersion
        };
    }


    public static function createHashFromString(string $string, int $length): string
    {
        return substr(hash('sha256', $string), 0, $length);
    }

    public function getKeyCodeFromRequest(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        $keyCode = $request?->query->get('key');
        $keyCode ??= $request?->request->get('key');
        $keyCode ??= $request?->cookies->get('key');

        return $keyCode;
    }

    public function changeAppSecretTemporarily(string $newAppSecret): void
    {
        $this->appSecret = $newAppSecret;
    }

}