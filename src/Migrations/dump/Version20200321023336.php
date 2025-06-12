<?php

declare(strict_types=1);

namespace Dump\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200321023336 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE newsletter (id BIGINT AUTO_INCREMENT NOT NULL, email VARCHAR(100) NOT NULL, created_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_7E8585C8E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id BIGINT AUTO_INCREMENT NOT NULL, store_id BIGINT DEFAULT NULL, user_id BIGINT DEFAULT NULL, invoice VARCHAR(100) NOT NULL, total NUMERIC(15, 2) NOT NULL, status VARCHAR(50) DEFAULT \'pending\' NOT NULL, note LONGTEXT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, address_url VARCHAR(255) DEFAULT NULL, address_map VARCHAR(255) DEFAULT NULL, address_lat NUMERIC(10, 8) DEFAULT NULL, address_lng NUMERIC(11, 8) DEFAULT NULL, map_place_id LONGTEXT DEFAULT NULL, post_code VARCHAR(10) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, district VARCHAR(100) DEFAULT NULL, province VARCHAR(100) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F529939890651744 (invoice), INDEX IDX_F5299398B092A811 (store_id), INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_payment (id BIGINT AUTO_INCREMENT NOT NULL, order_id BIGINT DEFAULT NULL, invoice VARCHAR(100) NOT NULL, name VARCHAR(200) NOT NULL, email VARCHAR(100) NOT NULL, type VARCHAR(100) NOT NULL, date DATE NOT NULL, attachment VARCHAR(255) NOT NULL, nominal NUMERIC(15, 2) NOT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9B522D468D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_product (id VARCHAR(255) NOT NULL, order_id BIGINT DEFAULT NULL, product_id BIGINT DEFAULT NULL, quantity BIGINT DEFAULT 0 NOT NULL, price NUMERIC(15, 2) NOT NULL, total_price NUMERIC(15, 2) NOT NULL, note LONGTEXT DEFAULT NULL, INDEX IDX_2530ADE68D9F6D38 (order_id), INDEX IDX_2530ADE64584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id BIGINT AUTO_INCREMENT NOT NULL, store_id BIGINT DEFAULT NULL, name VARCHAR(200) NOT NULL, slug VARCHAR(200) NOT NULL, category VARCHAR(255) DEFAULT NULL, keywords VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, note LONGTEXT DEFAULT NULL, quantity INT NOT NULL, price NUMERIC(15, 2) NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, dir_slug VARCHAR(100) DEFAULT NULL, featured TINYINT(1) NOT NULL, view_count BIGINT NOT NULL, rating_count BIGINT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), INDEX IDX_D34A04ADB092A811 (store_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_file (id BIGINT AUTO_INCREMENT NOT NULL, product_id BIGINT DEFAULT NULL, file_name VARCHAR(100) NOT NULL, file_type VARCHAR(20) DEFAULT \'image\' NOT NULL, file_mime_type VARCHAR(20) NOT NULL, file_path VARCHAR(200) NOT NULL, file_status VARCHAR(20) DEFAULT \'draft\' NOT NULL, is_default TINYINT(1) NOT NULL, sort INT NOT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_17714B14584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_review (id BIGINT AUTO_INCREMENT NOT NULL, product_id BIGINT DEFAULT NULL, user_id BIGINT DEFAULT NULL, review LONGTEXT DEFAULT NULL, rating SMALLINT DEFAULT 0 NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_1B3FC0624584665A (product_id), INDEX IDX_1B3FC062A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE setting (id BIGINT AUTO_INCREMENT NOT NULL, slug VARCHAR(30) NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(30) NOT NULL, default_value LONGTEXT DEFAULT NULL, options LONGTEXT DEFAULT NULL, namespace VARCHAR(50) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9F74B898989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE store (id BIGINT AUTO_INCREMENT NOT NULL, user_id BIGINT DEFAULT NULL, name VARCHAR(200) NOT NULL, slug VARCHAR(200) NOT NULL, color VARCHAR(255) DEFAULT NULL, theme VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_FF575877989D9B62 (slug), INDEX IDX_FF575877A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id BIGINT AUTO_INCREMENT NOT NULL, username VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, email_canonical VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, dir_slug VARCHAR(100) DEFAULT NULL, role VARCHAR(100) DEFAULT NULL, activation_code VARCHAR(255) DEFAULT NULL, forgot_password_code VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_deleted TINYINT(1) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, gender VARCHAR(20) DEFAULT NULL, photo_profile VARCHAR(255) DEFAULT NULL, banner_profile VARCHAR(255) DEFAULT NULL, newsletter TINYINT(1) NOT NULL, dob DATE DEFAULT NULL, ip_address VARCHAR(20) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_address (id BIGINT AUTO_INCREMENT NOT NULL, user_id BIGINT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, address_url VARCHAR(255) DEFAULT NULL, address_map VARCHAR(255) DEFAULT NULL, address_lat NUMERIC(10, 8) DEFAULT NULL, address_lng NUMERIC(11, 8) DEFAULT NULL, map_place_id LONGTEXT DEFAULT NULL, post_code VARCHAR(10) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, district VARCHAR(100) DEFAULT NULL, province VARCHAR(100) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_5543718BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B092A811 FOREIGN KEY (store_id) REFERENCES store (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE order_payment ADD CONSTRAINT FK_9B522D468D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE68D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE64584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB092A811 FOREIGN KEY (store_id) REFERENCES store (id)');
        $this->addSql('ALTER TABLE product_file ADD CONSTRAINT FK_17714B14584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0624584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC062A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE store ADD CONSTRAINT FK_FF575877A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_address ADD CONSTRAINT FK_5543718BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE order_payment DROP FOREIGN KEY FK_9B522D468D9F6D38');
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE68D9F6D38');
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE64584665A');
        $this->addSql('ALTER TABLE product_file DROP FOREIGN KEY FK_17714B14584665A');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC0624584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B092A811');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB092A811');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC062A76ED395');
        $this->addSql('ALTER TABLE store DROP FOREIGN KEY FK_FF575877A76ED395');
        $this->addSql('ALTER TABLE user_address DROP FOREIGN KEY FK_5543718BA76ED395');
        $this->addSql('DROP TABLE newsletter');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_payment');
        $this->addSql('DROP TABLE order_product');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_file');
        $this->addSql('DROP TABLE product_review');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE store');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_address');
    }
}
