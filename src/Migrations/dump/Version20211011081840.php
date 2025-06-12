<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211011081840 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gosend ADD seller_address_detail VARCHAR(255) DEFAULT NULL, ADD buyer_address_name VARCHAR(255) DEFAULT NULL, ADD buyer_address_detail VARCHAR(255) DEFAULT NULL, ADD insurance_details VARCHAR(255) DEFAULT NULL, ADD cancel_description VARCHAR(255) DEFAULT NULL, CHANGE cancellation_reason seller_address_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD doku_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_file CHANGE file_name file_name VARCHAR(100) NOT NULL, CHANGE file_path file_path VARCHAR(200) NOT NULL');
        $this->addSql('ALTER TABLE store CHANGE status status VARCHAR(20) DEFAULT NULL, CHANGE brand brand VARCHAR(200) DEFAULT NULL, CHANGE tnc tnc SMALLINT DEFAULT 0');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gosend ADD cancellation_reason VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP seller_address_name, DROP seller_address_detail, DROP buyer_address_name, DROP buyer_address_detail, DROP insurance_details, DROP cancel_description');
        $this->addSql('ALTER TABLE `order` DROP doku_id');
        $this->addSql('ALTER TABLE product_file CHANGE file_name file_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE file_path file_path VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE store CHANGE brand brand VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE tnc tnc SMALLINT DEFAULT NULL, CHANGE status status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
