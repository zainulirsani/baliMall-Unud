<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210218133247 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE virtual_account (id BIGINT AUTO_INCREMENT NOT NULL, invoice VARCHAR(255) DEFAULT NULL, record_id VARCHAR(255) DEFAULT NULL, bill_number VARCHAR(255) DEFAULT NULL, transaction_id VARCHAR(255) DEFAULT NULL, reference_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, amount NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, status VARCHAR(255) DEFAULT NULL, institution VARCHAR(255) DEFAULT NULL, paid_date VARCHAR(255) DEFAULT NULL, paid_status VARCHAR(255) DEFAULT NULL, kd_user VARCHAR(255) DEFAULT NULL, response LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX virtual_account_invoice_idx (invoice), INDEX virtual_account_record_id_idx (record_id), INDEX virtual_account_bill_number_idx (bill_number), INDEX virtual_account_transaction_id_idx (transaction_id), INDEX virtual_account_reference_id_idx (reference_id), INDEX virtual_account_status_idx (status), INDEX virtual_account_paid_status_idx (paid_status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE INDEX qris_invoice_idx ON qris (invoice)');
        $this->addSql('CREATE INDEX qris_bill_number_idx ON qris (bill_number)');
        $this->addSql('CREATE INDEX qris_trx_id_idx ON qris (trx_id)');
        $this->addSql('CREATE INDEX qris_trx_status_idx ON qris (trx_status)');
        $this->addSql('CREATE INDEX qris_reference_number_idx ON qris (reference_number)');
        $this->addSql('CREATE INDEX qris_qr_id_idx ON qris (qr_id)');
        $this->addSql('CREATE INDEX qris_qr_status_idx ON qris (qr_status)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE virtual_account');
        $this->addSql('DROP INDEX qris_invoice_idx ON qris');
        $this->addSql('DROP INDEX qris_bill_number_idx ON qris');
        $this->addSql('DROP INDEX qris_trx_id_idx ON qris');
        $this->addSql('DROP INDEX qris_trx_status_idx ON qris');
        $this->addSql('DROP INDEX qris_reference_number_idx ON qris');
        $this->addSql('DROP INDEX qris_qr_id_idx ON qris');
        $this->addSql('DROP INDEX qris_qr_status_idx ON qris');
    }
}
