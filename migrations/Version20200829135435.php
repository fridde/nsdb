<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200829135435 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groups ADD school_id VARCHAR(255) DEFAULT NULL, ADD name VARCHAR(255) DEFAULT NULL, ADD segment VARCHAR(255) DEFAULT NULL, ADD start_year SMALLINT DEFAULT NULL, ADD number_students SMALLINT DEFAULT NULL, ADD food LONGTEXT DEFAULT NULL, ADD info LONGTEXT DEFAULT NULL, ADD status SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970C32A47EE FOREIGN KEY (school_id) REFERENCES schools (id)');
        $this->addSql('CREATE INDEX IDX_F06D3970C32A47EE ON groups (school_id)');
        $this->addSql('ALTER TABLE topics CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE visits CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970C32A47EE');
        $this->addSql('DROP INDEX IDX_F06D3970C32A47EE ON groups');
        $this->addSql('ALTER TABLE groups DROP school_id, DROP name, DROP segment, DROP start_year, DROP number_students, DROP food, DROP info, DROP status');
        $this->addSql('ALTER TABLE topics CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE visits CHANGE id id INT NOT NULL');
    }
}
