<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201026131746 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mail_queue (id VARCHAR(255) NOT NULL, connection VARCHAR(255) DEFAULT \'default\' NOT NULL, batch VARCHAR(255) DEFAULT NULL, entity_id BIGINT DEFAULT 0 NOT NULL, entity_name VARCHAR(255) DEFAULT NULL, payload LONGTEXT DEFAULT NULL, success INT DEFAULT 0 NOT NULL, failed INT DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT NULL, INDEX mail_queue_connection_idx (connection), INDEX mail_queue_batch_idx (batch), INDEX mail_queue_entity_id_idx (entity_id), INDEX mail_queue_entity_name_idx (entity_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE mail_queue');
    }
}
