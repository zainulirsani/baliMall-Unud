<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210108115917 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE qris (id BIGINT AUTO_INCREMENT NOT NULL, invoice VARCHAR(255) DEFAULT NULL, bill_number VARCHAR(255) DEFAULT NULL, record_id BIGINT DEFAULT 0 NOT NULL, trx_id BIGINT DEFAULT 0 NOT NULL, trx_date VARCHAR(255) DEFAULT NULL, trx_status VARCHAR(255) DEFAULT NULL, trx_status_detail LONGTEXT DEFAULT NULL, reference_number VARCHAR(255) DEFAULT NULL, amount NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, tips NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, total_amount NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, qr_id BIGINT DEFAULT 0 NOT NULL, qr_value LONGTEXT DEFAULT NULL, qr_image LONGTEXT DEFAULT NULL, qr_status VARCHAR(255) DEFAULT NULL, nmid VARCHAR(255) DEFAULT NULL, mid VARCHAR(255) DEFAULT NULL, merchant_name VARCHAR(255) DEFAULT NULL, product_code VARCHAR(255) DEFAULT NULL, issuer_name VARCHAR(255) DEFAULT NULL, response_code VARCHAR(255) DEFAULT NULL, mdr_percentage VARCHAR(255) DEFAULT NULL, net_nominal VARCHAR(255) DEFAULT NULL, branch_code VARCHAR(255) DEFAULT NULL, refund_date VARCHAR(255) DEFAULT NULL, created_date VARCHAR(255) DEFAULT NULL, expired_date VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE qris');
    }
}
