<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200511145914 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE chat (id VARCHAR(255) NOT NULL, room VARCHAR(255) NOT NULL, initiator BIGINT NOT NULL, participant BIGINT NOT NULL, type VARCHAR(100) DEFAULT NULL, status VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX chat_room_idx (room), INDEX chat_initiator_idx (initiator), INDEX chat_participant_idx (participant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chat_message (id VARCHAR(255) NOT NULL, room VARCHAR(255) NOT NULL, sender BIGINT NOT NULL, sender_read TINYINT(1) NOT NULL, recipient BIGINT NOT NULL, recipient_read TINYINT(1) NOT NULL, message LONGTEXT DEFAULT NULL, deleted LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX chat_room_idx (room), INDEX chat_sender_idx (sender), INDEX chat_recipient_idx (recipient), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE chat_message');
    }
}
