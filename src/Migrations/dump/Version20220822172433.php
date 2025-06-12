<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220822172433 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `order` ADD djp_response_item LONGTEXT DEFAULT NULL, ADD djp_response_order LONGTEXT DEFAULT NULL, ADD djp_response_shipping LONGTEXT DEFAULT NULL, CHANGE work_unit work_unit JSON DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_7C96A2A18D9F6D38 ON disbursement');
        $this->addSql('ALTER TABLE disbursement CHANGE ppn ppn DOUBLE PRECISION DEFAULT NULL, CHANGE pph pph DOUBLE PRECISION DEFAULT NULL, CHANGE bank_fee bank_fee DOUBLE PRECISION DEFAULT NULL, CHANGE management_fee management_fee DOUBLE PRECISION DEFAULT NULL, CHANGE other_fee other_fee DOUBLE PRECISION DEFAULT NULL, CHANGE total_product_price total_product_price DOUBLE PRECISION DEFAULT NULL, CHANGE logs logs LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE persentase_ppn persentase_ppn DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_pph persentase_pph DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_bank persentase_bank DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_management persentase_management DOUBLE PRECISION DEFAULT NULL, CHANGE persentase_other persentase_other DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE doku CHANGE response response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE midtrans CHANGE response response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `order` DROP djp_response_item, DROP djp_response_order, DROP djp_response_shipping, CHANGE work_unit work_unit LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE order_change_log CHANGE previous_values previous_values LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE current_values current_values LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE changes changes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE user user LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE order_product CHANGE fee fee NUMERIC(15, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_shipped_file DROP FOREIGN KEY FK_EAA4949B8D9F6D38');
        $this->addSql('ALTER TABLE order_shipped_file CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE order_shipped_file RENAME INDEX idx_eaa4949b8d9f6d38 TO IDX_EAA4949BFCDAEAAA');
        $this->addSql('ALTER TABLE product CHANGE is_pdn is_pdn VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_category ADD fee_nominal DOUBLE PRECISION DEFAULT NULL, CHANGE fee fee NUMERIC(15, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_file CHANGE file_name file_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE file_path file_path VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE store CHANGE brand brand VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE tnc tnc SMALLINT DEFAULT NULL, CHANGE status status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ip_address ip_address VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE lkpp_role lkpp_role VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_pic_document DROP FOREIGN KEY FK_DEBAF7EFA76ED395');
        $this->addSql('DROP INDEX IDX_DEBAF7EFA76ED395 ON user_pic_document');
        $this->addSql('ALTER TABLE user_pic_document CHANGE user_id user_id BIGINT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
    }
}
