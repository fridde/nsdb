<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200822212944 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topics ADD location_id INT DEFAULT NULL, ADD segment VARCHAR(255) DEFAULT NULL, ADD visit_order SMALLINT NOT NULL, ADD short_name VARCHAR(255) NOT NULL, ADD long_name VARCHAR(255) DEFAULT NULL, ADD food VARCHAR(255) DEFAULT NULL, ADD food_order SMALLINT DEFAULT NULL, ADD url VARCHAR(255) DEFAULT NULL, ADD order_is_relevant TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE topics ADD CONSTRAINT FK_91F6463964D218E FOREIGN KEY (location_id) REFERENCES locations (id)');
        $this->addSql('CREATE INDEX IDX_91F6463964D218E ON topics (location_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topics DROP FOREIGN KEY FK_91F6463964D218E');
        $this->addSql('DROP INDEX IDX_91F6463964D218E ON topics');
        $this->addSql('ALTER TABLE topics DROP location_id, DROP segment, DROP visit_order, DROP short_name, DROP long_name, DROP food, DROP food_order, DROP url, DROP order_is_relevant');
    }
}
