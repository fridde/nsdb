<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200905101541 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE colleagues_visits (visit_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_2240324B75FA0FF2 (visit_id), INDEX IDX_2240324BA76ED395 (user_id), PRIMARY KEY(visit_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE colleagues_visits ADD CONSTRAINT FK_2240324B75FA0FF2 FOREIGN KEY (visit_id) REFERENCES visits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE colleagues_visits ADD CONSTRAINT FK_2240324BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages ADD user_id INT DEFAULT NULL, ADD subject SMALLINT NOT NULL, ADD carrier SMALLINT NOT NULL, ADD date DATETIME(6) NOT NULL');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_DB021E96A76ED395 ON messages (user_id)');
        $this->addSql('ALTER TABLE visits ADD group_id INT DEFAULT NULL, ADD topic_id INT DEFAULT NULL, ADD date DATETIME(6) NOT NULL, ADD confirmed TINYINT(1) NOT NULL, ADD time VARCHAR(255) DEFAULT NULL, ADD status TINYINT(1) NOT NULL, ADD bus_is_booked TINYINT(1) NOT NULL, ADD food_is_booked TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE visits ADD CONSTRAINT FK_444839EAFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id)');
        $this->addSql('ALTER TABLE visits ADD CONSTRAINT FK_444839EA1F55203D FOREIGN KEY (topic_id) REFERENCES topics (id)');
        $this->addSql('CREATE INDEX IDX_444839EAFE54D947 ON visits (group_id)');
        $this->addSql('CREATE INDEX IDX_444839EA1F55203D ON visits (topic_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE colleagues_visits');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96A76ED395');
        $this->addSql('DROP INDEX IDX_DB021E96A76ED395 ON messages');
        $this->addSql('ALTER TABLE messages DROP user_id, DROP subject, DROP carrier, DROP date');
        $this->addSql('ALTER TABLE visits DROP FOREIGN KEY FK_444839EAFE54D947');
        $this->addSql('ALTER TABLE visits DROP FOREIGN KEY FK_444839EA1F55203D');
        $this->addSql('DROP INDEX IDX_444839EAFE54D947 ON visits');
        $this->addSql('DROP INDEX IDX_444839EA1F55203D ON visits');
        $this->addSql('ALTER TABLE visits DROP group_id, DROP topic_id, DROP date, DROP confirmed, DROP time, DROP status, DROP bus_is_booked, DROP food_is_booked');
    }
}
