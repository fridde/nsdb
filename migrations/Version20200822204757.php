<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200822204757 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locations ADD name VARCHAR(255) NOT NULL, ADD coordinates VARCHAR(255) DEFAULT NULL, ADD description VARCHAR(255) DEFAULT NULL, ADD bus_id SMALLINT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_17E64ABA2546731D ON locations (bus_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_17E64ABA2546731D ON locations');
        $this->addSql('ALTER TABLE locations DROP name, DROP coordinates, DROP description, DROP bus_id');
    }
}
