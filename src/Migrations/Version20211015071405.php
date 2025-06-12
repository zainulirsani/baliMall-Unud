<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211015071405 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

//        $this->addSql('ALTER TABLE doku ADD customer_pan VARCHAR(255) DEFAULT NULL, ADD txn_date VARCHAR(255) DEFAULT NULL, ADD terminal_id VARCHAR(255) DEFAULT NULL, ADD issuer_id VARCHAR(255) DEFAULT NULL, ADD issuer_name VARCHAR(255) DEFAULT NULL, ADD words VARCHAR(255) DEFAULT NULL, ADD customer_name VARCHAR(255) DEFAULT NULL, ADD origin VARCHAR(255) DEFAULT NULL, ADD convenience_fee VARCHAR(255) DEFAULT NULL, ADD acquirer VARCHAR(255) DEFAULT NULL, ADD merchant_pan VARCHAR(255) DEFAULT NULL, ADD reference_id VARCHAR(255) DEFAULT NULL, ADD invoice_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

//        $this->addSql('ALTER TABLE doku DROP customer_pan, DROP txn_date, DROP terminal_id, DROP issuer_id, DROP issuer_name, DROP words, DROP customer_name, DROP origin, DROP convenience_fee, DROP acquirer, DROP merchant_pan, DROP reference_id');
    }
}
