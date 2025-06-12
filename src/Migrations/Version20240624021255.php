<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240624021255 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_bpd_binding ADD user_id BIGINT DEFAULT NULL, ADD ott VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user_bpd_binding ADD CONSTRAINT FK_7FDE0B81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_7FDE0B81A76ED395 ON user_bpd_binding (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_bpd_binding DROP FOREIGN KEY FK_7FDE0B81A76ED395');
        $this->addSql('DROP INDEX IDX_7FDE0B81A76ED395 ON user_bpd_binding');
        $this->addSql('ALTER TABLE user_bpd_binding DROP user_id, DROP ott');
    }
}
