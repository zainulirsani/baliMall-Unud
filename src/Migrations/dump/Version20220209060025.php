<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220209060025 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE disbursement ADD persentase_ppn DOUBLE PRECISION DEFAULT NULL, ADD persentase_pph DOUBLE PRECISION DEFAULT NULL, ADD persentase_bank DOUBLE PRECISION DEFAULT NULL, ADD persentase_management DOUBLE PRECISION DEFAULT NULL, ADD persentase_other DOUBLE PRECISION DEFAULT NULL');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE disbursement DROP persentase_ppn, DROP persentase_pph, DROP persentase_bank, DROP persentase_management, DROP persentase_other, CHANGE ppn ppn DOUBLE PRECISION DEFAULT \'NULL\', CHANGE pph pph DOUBLE PRECISION DEFAULT \'NULL\', CHANGE bank_fee bank_fee DOUBLE PRECISION DEFAULT \'NULL\', CHANGE management_fee management_fee DOUBLE PRECISION DEFAULT \'NULL\', CHANGE other_fee other_fee DOUBLE PRECISION DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE total_product_price total_product_price DOUBLE PRECISION DEFAULT \'NULL\', CHANGE total total DOUBLE PRECISION DEFAULT \'NULL\', CHANGE logs logs LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');

    }
}
