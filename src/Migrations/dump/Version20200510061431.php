<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200510061431 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product_review ADD order_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0628D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1B3FC0628D9F6D38 ON product_review (order_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC0628D9F6D38');
        $this->addSql('DROP INDEX IDX_1B3FC0628D9F6D38 ON product_review');
        $this->addSql('ALTER TABLE product_review DROP order_id');
    }
}
