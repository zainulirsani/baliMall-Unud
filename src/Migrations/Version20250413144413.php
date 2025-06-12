<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413144413 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE TABLE `document_approval` (`id` INT NOT NULL AUTO_INCREMENT , `order_id` INT NOT NULL , `type_document` ENUM('bast', 'label', 'invoice', 'performa_invoice', 'performa_invoice_ls', 'receipt', 'spk', 'negotiation', 'basp', 'spk_new_ls', 'spk_new', 'bapd','bapd_ls','bast_ls','receipt_ls' , 'surat-pengiriman-parsial') NOT NULL , `approved_by` INT NOT NULL , `approved_at` TIMESTAMP NOT NULL , `created_at` TIMESTAMP NOT NULL , PRIMARY KEY (`id`))");
        
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP TABLE `document_approval`");
        
    }
}
