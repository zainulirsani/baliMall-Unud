<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221121042958 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE disbursement ADD status_change_time DATETIME DEFAULT NULL, CHANGE total_product_price total_product_price DOUBLE PRECISION NOT NULL, CHANGE ppn ppn DOUBLE PRECISION NOT NULL, CHANGE pph pph DOUBLE PRECISION NOT NULL, CHANGE bank_fee bank_fee DOUBLE PRECISION NOT NULL, CHANGE management_fee management_fee DOUBLE PRECISION NOT NULL, CHANGE other_fee other_fee DOUBLE PRECISION NOT NULL, CHANGE logs logs JSON DEFAULT NULL, CHANGE persentase_ppn persentase_ppn DOUBLE PRECISION NOT NULL, CHANGE persentase_pph persentase_pph DOUBLE PRECISION NOT NULL, CHANGE persentase_bank persentase_bank DOUBLE PRECISION NOT NULL, CHANGE persentase_management persentase_management DOUBLE PRECISION NOT NULL, CHANGE persentase_other persentase_other DOUBLE PRECISION NOT NULL');
        
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE disbursement DROP status_change_time, CHANGE ppn ppn DOUBLE PRECISION DEFAULT NULL, CHANGE pph pph DOUBLE PRECISION DEFAULT NULL, CHANGE bank_fee bank_fee DOUBLE PRECISION DEFAULT NULL, CHANGE management_fee management_fee DOUBLE PRECISION DEFAULT NULL, CHANGE other_fee other_fee DOUBLE PRECISION DEFAULT NULL, CHANGE total_product_price total_product_price DOUBLE PRECISION DEFAULT NULL, CHANGE logs logs LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE persentase_ppn persentase_ppn DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_pph persentase_pph DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_bank persentase_bank DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_management persentase_management DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_other persentase_other DOUBLE PRECISION DEFAULT NULL');
    }
}
