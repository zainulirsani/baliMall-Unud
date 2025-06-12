<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220308063242 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE disbursement ADD order_shipping_price DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE disbursement DROP order_shipping_price, CHANGE ppn ppn DOUBLE PRECISION DEFAULT \'NULL\', CHANGE pph pph DOUBLE PRECISION DEFAULT \'NULL\', CHANGE bank_fee bank_fee DOUBLE PRECISION DEFAULT \'NULL\', CHANGE management_fee management_fee DOUBLE PRECISION DEFAULT \'NULL\', CHANGE other_fee other_fee DOUBLE PRECISION DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE total total DOUBLE PRECISION DEFAULT \'NULL\', CHANGE logs logs LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE persentase_ppn persentase_ppn DOUBLE PRECISION DEFAULT \'NULL\', CHANGE persentase_pph persentase_pph DOUBLE PRECISION DEFAULT \'NULL\', CHANGE persentase_bank persentase_bank DOUBLE PRECISION DEFAULT \'NULL\', CHANGE persentase_management persentase_management DOUBLE PRECISION DEFAULT \'NULL\', CHANGE persentase_other persentase_other DOUBLE PRECISION DEFAULT \'NULL\', CHANGE payment_proof payment_proof VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE rekening_name rekening_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE bank_name bank_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE nomor_rekening nomor_rekening VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
    }
}
