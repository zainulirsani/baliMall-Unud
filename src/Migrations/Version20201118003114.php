<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201118003114 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE order_negotiation (id BIGINT AUTO_INCREMENT NOT NULL, order_id BIGINT DEFAULT NULL, product_id BIGINT DEFAULT 0 NOT NULL, submitted_by BIGINT DEFAULT 0 NOT NULL, submitted_as VARCHAR(255) DEFAULT NULL, negotiated_price NUMERIC(15, 2) NOT NULL, execution_time VARCHAR(255) DEFAULT NULL, is_approved TINYINT(1) DEFAULT \'0\' NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_C2AEA1E8D9F6D38 (order_id), INDEX order_negotiation_product_id_idx (product_id), INDEX order_negotiation_submitted_by_idx (submitted_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_negotiation ADD CONSTRAINT FK_C2AEA1E8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `order` ADD negotiation_status VARCHAR(255) DEFAULT \'none\' NOT NULL, ADD execution_time VARCHAR(255) DEFAULT NULL, ADD job_package_name VARCHAR(255) DEFAULT NULL, ADD fiscal_year VARCHAR(255) DEFAULT NULL, ADD source_of_fund VARCHAR(255) DEFAULT NULL, ADD receipt_file VARCHAR(255) DEFAULT NULL, ADD work_order_letter_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_product ADD price_before_negotiation NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE order_negotiation');
        $this->addSql('ALTER TABLE `order` DROP negotiation_status, DROP execution_time, DROP job_package_name, DROP fiscal_year, DROP source_of_fund, DROP receipt_file, DROP work_order_letter_file');
        $this->addSql('ALTER TABLE order_product DROP price_before_negotiation');
    }
}
