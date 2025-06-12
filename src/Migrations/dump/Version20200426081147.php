<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200426081147 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `order` ADD shipping_courier VARCHAR(255) DEFAULT NULL, ADD shipping_price NUMERIC(15, 2) NOT NULL, ADD tracking_code VARCHAR(255) DEFAULT NULL, ADD city_id BIGINT DEFAULT 0 NOT NULL, ADD district_id BIGINT DEFAULT 0 NOT NULL, ADD province_id BIGINT DEFAULT 0 NOT NULL, ADD country_id BIGINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE product ADD weight VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_address ADD city_id BIGINT DEFAULT 0 NOT NULL, ADD district_id BIGINT DEFAULT 0 NOT NULL, ADD province_id BIGINT DEFAULT 0 NOT NULL, ADD country_id BIGINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `order` DROP shipping_courier, DROP shipping_price, DROP tracking_code, DROP city_id, DROP district_id, DROP province_id, DROP country_id');
        $this->addSql('ALTER TABLE product DROP weight');
        $this->addSql('ALTER TABLE user_address DROP city_id, DROP district_id, DROP province_id, DROP country_id');
    }
}
