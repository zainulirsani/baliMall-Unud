<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200722133629 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE voucher (id BIGINT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, percentage BIGINT DEFAULT 0 NOT NULL, amount NUMERIC(15, 2) NOT NULL, start_at DATETIME DEFAULT NULL, end_at DATETIME DEFAULT NULL, usage_limit BIGINT DEFAULT 0 NOT NULL, usage_per_user BIGINT DEFAULT 0 NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, qr_image VARCHAR(255) DEFAULT NULL, valid_for LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX voucher_code_idx (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voucher_used_log (id VARCHAR(255) NOT NULL, voucher_id BIGINT DEFAULT 0 NOT NULL, user_id BIGINT DEFAULT 0 NOT NULL, order_id BIGINT DEFAULT 0 NOT NULL, voucher_amount NUMERIC(15, 2) NOT NULL, order_amount NUMERIC(15, 2) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX voucher_used_log_voucher_id_idx (voucher_id), INDEX voucher_used_log_user_id_idx (user_id), INDEX voucher_used_log_order_id_idx (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE voucher');
        $this->addSql('DROP TABLE voucher_used_log');
    }
}
