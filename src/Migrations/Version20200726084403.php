<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200726084403 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notification ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX notification_buyer_id_idx ON notification (buyer_id)');
        $this->addSql('CREATE INDEX notification_seller_id_idx ON notification (seller_id)');
        $this->addSql('ALTER TABLE product_review ADD attachment VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE voucher_used_log CHANGE id id BIGINT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX notification_buyer_id_idx ON notification');
        $this->addSql('DROP INDEX notification_seller_id_idx ON notification');
        $this->addSql('ALTER TABLE notification DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE product_review DROP attachment');
        $this->addSql('ALTER TABLE voucher_used_log CHANGE id id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
