<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200429104902 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE store ADD address VARCHAR(255) DEFAULT NULL, ADD address_url VARCHAR(255) DEFAULT NULL, ADD address_map VARCHAR(255) DEFAULT NULL, ADD address_lat NUMERIC(10, 8) DEFAULT NULL, ADD address_lng NUMERIC(11, 8) DEFAULT NULL, ADD map_place_id LONGTEXT DEFAULT NULL, ADD post_code VARCHAR(10) DEFAULT NULL, ADD city VARCHAR(100) DEFAULT NULL, ADD city_id BIGINT DEFAULT 0 NOT NULL, ADD district VARCHAR(100) DEFAULT NULL, ADD district_id BIGINT DEFAULT 0 NOT NULL, ADD province VARCHAR(100) DEFAULT NULL, ADD province_id BIGINT DEFAULT 0 NOT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD country_id BIGINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE store DROP address, DROP address_url, DROP address_map, DROP address_lat, DROP address_lng, DROP map_place_id, DROP post_code, DROP city, DROP city_id, DROP district, DROP district_id, DROP province, DROP province_id, DROP country, DROP country_id');
    }
}
