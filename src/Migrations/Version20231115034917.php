<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231115034917 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `order` ADD cancel_file LONGTEXT DEFAULT NULL, DROP is_order_under, CHANGE gosend_booking_id gosend_booking_id INT DEFAULT NULL, CHANGE work_unit work_unit JSON DEFAULT NULL, CHANGE shipped_method shipped_method VARCHAR(255) DEFAULT NULL, CHANGE self_courier_telp self_courier_telp VARCHAR(255) DEFAULT NULL, CHANGE user_cancel_order user_cancel_order VARCHAR(255) DEFAULT NULL, CHANGE djp_report_status djp_report_status VARCHAR(255) DEFAULT NULL, CHANGE ppk_telp ppk_telp VARCHAR(255) DEFAULT NULL, CHANGE treasurer_telp treasurer_telp VARCHAR(255) DEFAULT NULL, CHANGE tax_type tax_type VARCHAR(255) DEFAULT NULL, CHANGE treasurer_pph treasurer_pph VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE `order` ADD is_order_under TINYINT(1) DEFAULT NULL, DROP cancel_file, CHANGE gosend_booking_id gosend_booking_id BIGINT DEFAULT NULL, CHANGE work_unit work_unit LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE djp_report_status djp_report_status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE shipped_method shipped_method VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE self_courier_telp self_courier_telp VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE user_cancel_order user_cancel_order VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ppk_telp ppk_telp VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE treasurer_telp treasurer_telp VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE tax_type tax_type VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE treasurer_pph treasurer_pph VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
