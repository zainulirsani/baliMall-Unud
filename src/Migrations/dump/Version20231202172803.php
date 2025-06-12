<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231202172803 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE access_token_bpd (id INT AUTO_INCREMENT NOT NULL, token LONGTEXT NOT NULL, expired_date DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bpd_cc (id INT AUTO_INCREMENT NOT NULL, orders_id BIGINT NOT NULL, trx_id BIGINT NOT NULL, external_id BIGINT NOT NULL, amount NUMERIC(16, 2) NOT NULL, status VARCHAR(20) NOT NULL, reference_no VARCHAR(12) NOT NULL, cpan VARCHAR(19) NOT NULL, ott VARCHAR(8) NOT NULL, expired_in DATETIME NOT NULL, request_data LONGTEXT NOT NULL, response LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_44E3EE62CFFE9AD6 (orders_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refund_bpd (id INT AUTO_INCREMENT NOT NULL, cc_bpd_id INT NOT NULL, request_data LONGTEXT NOT NULL, response LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4C87CC2299B4A521 (cc_bpd_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bpd_cc ADD CONSTRAINT FK_44E3EE62CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE refund_bpd ADD CONSTRAINT FK_4C87CC2299B4A521 FOREIGN KEY (cc_bpd_id) REFERENCES bpd_cc (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE refund_bpd DROP FOREIGN KEY FK_4C87CC2299B4A521');
        $this->addSql('DROP TABLE access_token_bpd');
        $this->addSql('DROP TABLE bpd_cc');
        $this->addSql('DROP TABLE refund_bpd');
    }
}
