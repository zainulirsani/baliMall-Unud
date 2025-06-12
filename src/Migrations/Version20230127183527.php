<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230127183527 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `order` ADD other_document_name VARCHAR(255) DEFAULT NULL, DROP received_at, DROP spk_file, DROP bapd_file, DROP other_pph_persentase, CHANGE work_unit work_unit JSON DEFAULT NULL, CHANGE cancel_status cancel_status VARCHAR(255) DEFAULT NULL, CHANGE user_cancel_order user_cancel_order VARCHAR(255) DEFAULT NULL, CHANGE ppk_email ppk_email VARCHAR(255) DEFAULT NULL, CHANGE treasurer_email treasurer_email VARCHAR(255) DEFAULT NULL, CHANGE ppk_telp ppk_telp VARCHAR(255) DEFAULT NULL, CHANGE treasurer_telp treasurer_telp VARCHAR(255) DEFAULT NULL, CHANGE tax_type tax_type VARCHAR(255) DEFAULT NULL, CHANGE treasurer_pph treasurer_pph VARCHAR(255) DEFAULT NULL, CHANGE treasurer_pph_nominal treasurer_pph_nominal VARCHAR(100) DEFAULT NULL');
        
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bank CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_7C96A2A18D9F6D38 ON disbursement');
        $this->addSql('ALTER TABLE disbursement ADD status_change_time DATETIME DEFAULT NULL, CHANGE ppn ppn DOUBLE PRECISION DEFAULT NULL, CHANGE pph pph DOUBLE PRECISION DEFAULT NULL, CHANGE bank_fee bank_fee DOUBLE PRECISION DEFAULT NULL, CHANGE management_fee management_fee DOUBLE PRECISION DEFAULT NULL, CHANGE other_fee other_fee DOUBLE PRECISION DEFAULT NULL, CHANGE total_product_price total_product_price DOUBLE PRECISION DEFAULT NULL, CHANGE logs logs LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE persentase_ppn persentase_ppn DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_pph persentase_pph DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_bank persentase_bank DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_management persentase_management DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_other persentase_other DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE doku CHANGE response response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE midtrans CHANGE response response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `order` ADD received_at DATETIME DEFAULT NULL, ADD spk_file LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, ADD bapd_file LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, ADD other_pph_persentase VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, DROP other_document_name, CHANGE work_unit work_unit LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE cancel_status cancel_status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, CHANGE user_cancel_order user_cancel_order VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, CHANGE ppk_email ppk_email VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, CHANGE treasurer_email treasurer_email VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, CHANGE ppk_telp ppk_telp VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, CHANGE treasurer_telp treasurer_telp VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, CHANGE tax_type tax_type INT DEFAULT NULL, CHANGE treasurer_pph treasurer_pph VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, CHANGE treasurer_pph_nominal treasurer_pph_nominal VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('ALTER TABLE order_change_log CHANGE previous_values previous_values LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE current_values current_values LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE changes changes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE user user LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE order_product CHANGE fee fee NUMERIC(15, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_shipped_file DROP FOREIGN KEY FK_EAA4949B8D9F6D38');
        $this->addSql('ALTER TABLE order_shipped_file CHANGE order_id order_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE order_shipped_file RENAME INDEX idx_eaa4949b8d9f6d38 TO IDX_EAA4949BFCDAEAAA');
        $this->addSql('ALTER TABLE product CHANGE is_pdn is_pdn VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_category ADD fee_nominal DOUBLE PRECISION DEFAULT NULL, CHANGE fee fee NUMERIC(15, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_file CHANGE file_name file_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE file_path file_path VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE store ADD is_used_erzap TINYINT(1) DEFAULT NULL, CHANGE brand brand VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE tnc tnc SMALLINT DEFAULT NULL, CHANGE status status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user ADD is_user_testing TINYINT(1) DEFAULT NULL, CHANGE first_name first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ip_address ip_address VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE surat_ijin_file surat_ijin_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE dokumen_file dokumen_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE lkpp_role lkpp_role VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_pic_document DROP FOREIGN KEY FK_DEBAF7EFA76ED395');
        $this->addSql('DROP INDEX IDX_DEBAF7EFA76ED395 ON user_pic_document');
        $this->addSql('ALTER TABLE user_pic_document CHANGE user_id user_id BIGINT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
    }
}
