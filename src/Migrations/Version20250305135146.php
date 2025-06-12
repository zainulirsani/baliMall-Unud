<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250305135146 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `role_has_permissions` (`id` INT NOT NULL AUTO_INCREMENT , `role_slug` VARCHAR(255) NOT NULL , `subrole_slug` VARCHAR(255) NULL , `permission_id` INT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `role_has_permissions`');
    }
}
