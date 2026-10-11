<?php


namespace App\Security\Key;


use Carbon\Carbon;

class Key
{
    public ?string $version = null;

    public const string TYPE_ANON = 'a';
    public const string TYPE_COOKIE = 'c';
    public const string TYPE_LOGIN = 'l';
    public const string TYPE_CRON = 't';
    public const string TYPE_URL = 'u';

    private const array VALID_TYPES = [
        self::TYPE_ANON,
        self::TYPE_COOKIE,
        self::TYPE_LOGIN,
        self::TYPE_CRON,
        self::TYPE_URL
    ];

    public const string SEPARATOR = '-';

    public function __construct(
        public ?string $type = null,
        public ?string $id = null,
        public ?string $salt = null,
        public ?string $hash = null
    )
    {
    }

    public static function create(): self
    {
        return new self();
    }


    public function getType(): ?string
    {
        return $this->type;
    }


    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function isType(string $type): bool
    {
        return $this->getType() === $type;
    }

    public function isValidType(): bool
    {
        return in_array($this->type, self::VALID_TYPES ,true);
    }


    public function getId(): string
    {
        return $this->id;
    }


    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }


    public function setSalt(string $salt = null): self
    {
        $this->salt = $salt ?? substr(md5(microtime()), 0, 5);

        return $this;
    }

    public function getHashLength(): int
    {
        return match ($this->type) {
            self::TYPE_ANON, self::TYPE_COOKIE => 64,
            self::TYPE_CRON => 16,
            self::TYPE_URL, self::TYPE_LOGIN => 8
        };
    }

    public static function getAllTypes(): array
    {
        return [self::TYPE_ANON, self::TYPE_COOKIE, self::TYPE_CRON, self::TYPE_URL];
    }

    /*
     * This hack helps to invalidate all codes during
     * the summer weeks and not at the end of the year
    */
    public static function getHalfAYearAgo(): int
    {
        self::setTestDateIfNecessary();
        return Carbon::today()->subDays(180)->year;
    }

    /*
     * This hack helps to invalidate the code at about 05:00 and not at midnight
    */
    public static function getDayFiveHoursAgo(): int
    {
        return Carbon::now()->subHours(5)->dayOfYear();
    }

    private static function setTestDateIfNecessary(): void
    {
        $testdate = $_REQUEST['testdate'] ?? null;
        if ($testdate !== null &&  (int)$_ENV['APP_DEBUG']) {
            Carbon::setTestNow($testdate);
        }
    }


}