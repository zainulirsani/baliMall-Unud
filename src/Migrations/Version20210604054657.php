<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210604054657 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE store ADD modal_usaha NUMERIC(15, 2) DEFAULT NULL, ADD total_manpower VARCHAR(255) DEFAULT NULL, ADD rekening_name VARCHAR(255) DEFAULT NULL, ADD bank_name VARCHAR(255) DEFAULT NULL, ADD nomor_rekening VARCHAR(255) DEFAULT NULL, ADD rekening_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD ktp_file VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE store DROP modal_usaha, DROP total_manpower, DROP rekening_name, DROP bank_name, DROP nomor_rekening, DROP rekening_file');
        $this->addSql('ALTER TABLE user DROP ktp_file');
    }
}
