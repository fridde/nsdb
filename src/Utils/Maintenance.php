<?php

namespace App\Utils;

use Carbon\Carbon;
use Spatie\DbDumper\Databases\MySql;

class Maintenance
{
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;

    public function __construct(array $dbSettings, array $ftpSettings)
    {
        $this->dbName = $dbSettings['name'];
        $this->dbUser = $dbSettings['user'];
        $this->dbPassword = $dbSettings['password'];
    }

    public function dumpDb(): void
    {
        $fileName = $this->dbName . '_backup@';
        $fileName .= str_replace([':', ' '], ['', '_'], Carbon::now()->toDateTimeString());
        $fileName .= '.sql';

        MySql::create()
            ->setDbName($this->dbName)
            ->setUserName($this->dbUser)
            ->setPassword($this->dbPassword)
            ->doNotUseColumnStatistics()
            ->dumpToFile('backup/' . $fileName);
    }

}