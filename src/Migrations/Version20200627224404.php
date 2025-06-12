<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200627224404 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE order_complaint (id BIGINT AUTO_INCREMENT NOT NULL, order_id BIGINT DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_resolved TINYINT(1) NOT NULL, resolved_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_E3A0494D8D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_complaint ADD CONSTRAINT FK_E3A0494D8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `order` ADD is_b2g_transaction TINYINT(1) DEFAULT \'0\' NOT NULL, ADD bast_file VARCHAR(255) DEFAULT NULL, ADD delivery_paper_file VARCHAR(255) DEFAULT NULL, ADD tax_invoice_file VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE order_complaint');
        $this->addSql('ALTER TABLE `order` DROP is_b2g_transaction, DROP bast_file, DROP delivery_paper_file, DROP tax_invoice_file');
    }
}
