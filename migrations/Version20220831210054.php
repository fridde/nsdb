<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220831210054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recordings ADD user_id INT DEFAULT NULL, DROP user');
        $this->addSql('ALTER TABLE recordings ADD CONSTRAINT FK_E9D79C6EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_E9D79C6EA76ED395 ON recordings (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recordings DROP FOREIGN KEY FK_E9D79C6EA76ED395');
        $this->addSql('DROP INDEX IDX_E9D79C6EA76ED395 ON recordings');
        $this->addSql('ALTER TABLE recordings ADD user VARCHAR(255) DEFAULT NULL, DROP user_id');
    }
}
