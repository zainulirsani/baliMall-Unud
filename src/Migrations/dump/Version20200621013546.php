<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200621013546 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE store_owner_log (id BIGINT AUTO_INCREMENT NOT NULL, store_id BIGINT DEFAULT 0 NOT NULL, current_owner BIGINT DEFAULT 0 NOT NULL, previous_owner BIGINT DEFAULT 0 NOT NULL, updated_by BIGINT DEFAULT 0 NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX store_owner_log_store_id_idx (store_id), INDEX store_owner_log_current_owner_idx (current_owner), INDEX store_owner_log_previous_owner_idx (previous_owner), INDEX store_owner_log_updated_by_idx (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE store_owner_log');
    }
}
