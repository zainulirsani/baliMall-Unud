<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200408223946 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE product_category (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(200) NOT NULL, slug VARCHAR(200) NOT NULL, heading VARCHAR(200) DEFAULT NULL, description LONGTEXT DEFAULT NULL, desktop_image VARCHAR(255) DEFAULT NULL, mobile_image VARCHAR(255) DEFAULT NULL, status TINYINT(1) NOT NULL, featured TINYINT(1) NOT NULL, sort INT NOT NULL, dir_slug VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_CDFC7356989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE product_category');
    }
}
