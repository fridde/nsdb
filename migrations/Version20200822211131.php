<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200822211131 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD school_id VARCHAR(255) DEFAULT NULL, ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL, ADD mobil VARCHAR(255) DEFAULT NULL, ADD mail VARCHAR(255) DEFAULT NULL, ADD role SMALLINT DEFAULT NULL, ADD acronym VARCHAR(255) DEFAULT NULL, ADD status SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9C32A47EE FOREIGN KEY (school_id) REFERENCES schools (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9C32A47EE ON users (school_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9C32A47EE');
        $this->addSql('DROP INDEX IDX_1483A5E9C32A47EE ON users');
        $this->addSql('ALTER TABLE users DROP school_id, DROP first_name, DROP last_name, DROP mobil, DROP mail, DROP role, DROP acronym, DROP status');
    }
}
