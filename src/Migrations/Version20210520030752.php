<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210520030752 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `order` ADD total_backup NUMERIC(15, 2) NOT NULL, ADD shipping_price_backup NUMERIC(15, 2) NOT NULL');
        $this->addSql('ALTER TABLE order_negotiation ADD tax_nominal_price NUMERIC(15, 2) NOT NULL, ADD tax_nominal_shipping NUMERIC(15, 2) DEFAULT \'0\' NOT NULL, ADD tax_value NUMERIC(15, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE user ADD ktp_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE store ADD modal_usaha NUMERIC(15, 2) DEFAULT \'0\' NULL, ADD total_manpower VARCHAR(255) DEFAULT NULL, ADD rekening_name VARCHAR(255) DEFAULT NULL, ADD bank_name VARCHAR(255) DEFAULT NULL, ADD nomor_rekening VARCHAR(255) DEFAULT NULL, rekening_file VARCHAR(255) DEFAULT NULL, sppkp_file VARCHAR(255) DEFAULT NULL  ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `order` DROP total_backup, DROP shipping_price_backup');
        $this->addSql('ALTER TABLE order_negotiation DROP tax_nominal_price, DROP tax_nominal_shipping, DROP tax_value');
    }
}
