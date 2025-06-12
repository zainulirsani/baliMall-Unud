<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210112150036 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user ADD lkpp_lpse_id VARCHAR(255) DEFAULT NULL, ADD lkpp_employee_id VARCHAR(255) DEFAULT NULL, ADD lkpp_groups VARCHAR(255) DEFAULT NULL, ADD lkpp_kldi VARCHAR(255) DEFAULT NULL, ADD lkpp_work_unit VARCHAR(255) DEFAULT NULL, ADD lkpp_token VARCHAR(255) DEFAULT NULL, ADD lkpp_token_expiration DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user DROP lkpp_lpse_id, DROP lkpp_employee_id, DROP lkpp_groups, DROP lkpp_kldi, DROP lkpp_work_unit, DROP lkpp_token, DROP lkpp_token_expiration');
    }
}
