<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211106210536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks ADD content LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE executed executed DATETIME(6) DEFAULT NULL');
        $this->addSql('ALTER TABLE visits ADD bus_status SMALLINT NOT NULL, DROP bus_is_booked');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP content, CHANGE executed executed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE visits ADD bus_is_booked TINYINT(1) NOT NULL, DROP bus_status');
    }
}
