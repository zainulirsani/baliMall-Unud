<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220804070945 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE disbursement CHANGE total_product_price total_product_price DOUBLE PRECISION NOT NULL, CHANGE ppn ppn DOUBLE PRECISION NOT NULL, CHANGE pph pph DOUBLE PRECISION NOT NULL, CHANGE bank_fee bank_fee DOUBLE PRECISION NOT NULL, CHANGE management_fee management_fee DOUBLE PRECISION NOT NULL, CHANGE other_fee other_fee DOUBLE PRECISION NOT NULL, CHANGE logs logs JSON DEFAULT NULL, CHANGE persentase_ppn persentase_ppn DOUBLE PRECISION NOT NULL, CHANGE persentase_pph persentase_pph DOUBLE PRECISION NOT NULL, CHANGE persentase_bank persentase_bank DOUBLE PRECISION NOT NULL, CHANGE persentase_management persentase_management DOUBLE PRECISION NOT NULL, CHANGE persentase_other persentase_other DOUBLE PRECISION NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C96A2A18D9F6D38 ON disbursement (order_id)');
        $this->addSql('ALTER TABLE doku CHANGE response response JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE midtrans CHANGE response response JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` CHANGE work_unit work_unit JSON DEFAULT NULL, CHANGE djp_report_status shipped_method VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_change_log CHANGE previous_values previous_values JSON NOT NULL, CHANGE current_values current_values JSON NOT NULL, CHANGE changes changes JSON DEFAULT NULL, CHANGE user user JSON NOT NULL');
        $this->addSql('ALTER TABLE order_product CHANGE fee fee INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE is_pdn is_pdn VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_category DROP fee_nominal, CHANGE fee fee INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_file CHANGE file_name file_name VARCHAR(100) NOT NULL, CHANGE file_path file_path VARCHAR(200) NOT NULL');
        $this->addSql('ALTER TABLE store CHANGE status status VARCHAR(20) DEFAULT NULL, CHANGE brand brand VARCHAR(200) DEFAULT NULL, CHANGE tnc tnc SMALLINT DEFAULT 0');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(100) DEFAULT NULL, CHANGE ip_address ip_address VARCHAR(40) DEFAULT NULL, CHANGE lkpp_role lkpp_role VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_7C96A2A18D9F6D38 ON disbursement');
        $this->addSql('ALTER TABLE disbursement CHANGE ppn ppn DOUBLE PRECISION DEFAULT NULL, CHANGE pph pph DOUBLE PRECISION DEFAULT NULL, CHANGE bank_fee bank_fee DOUBLE PRECISION DEFAULT NULL, CHANGE management_fee management_fee DOUBLE PRECISION DEFAULT NULL, CHANGE other_fee other_fee DOUBLE PRECISION DEFAULT NULL, CHANGE total_product_price total_product_price DOUBLE PRECISION DEFAULT NULL, CHANGE logs logs LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE persentase_ppn persentase_ppn DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_pph persentase_pph DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_bank persentase_bank DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_management persentase_management DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_other persentase_other DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE doku CHANGE response response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE midtrans CHANGE response response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `order` CHANGE work_unit work_unit LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE shipped_method djp_report_status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('ALTER TABLE order_change_log CHANGE previous_values previous_values LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE current_values current_values LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE changes changes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE user user LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE order_product CHANGE fee fee NUMERIC(15, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE is_pdn is_pdn VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_category ADD fee_nominal DOUBLE PRECISION DEFAULT NULL, CHANGE fee fee NUMERIC(15, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_file CHANGE file_name file_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE file_path file_path VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE store CHANGE brand brand VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE tnc tnc SMALLINT DEFAULT NULL, CHANGE status status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ip_address ip_address VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE lkpp_role lkpp_role VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
