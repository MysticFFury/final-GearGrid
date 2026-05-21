<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408173344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stock_log table for tracking stock quantity changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE stock_log (
                id INT AUTO_INCREMENT NOT NULL,
                action VARCHAR(50) NOT NULL,
                message LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                status VARCHAR(20) NOT NULL,
                user_name VARCHAR(100) DEFAULT NULL,
                user_role VARCHAR(255) DEFAULT NULL,
                product_id INT NOT NULL,
                old_quantity INT NOT NULL,
                new_quantity INT NOT NULL,
                quantity_change INT DEFAULT NULL,
                INDEX IDX_STOCK_LOG_PRODUCT (product_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE stock_log 
            ADD CONSTRAINT FK_STOCK_LOG_PRODUCT 
            FOREIGN KEY (product_id) 
            REFERENCES product (id) 
            ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_log DROP FOREIGN KEY FK_STOCK_LOG_PRODUCT');
        $this->addSql('DROP TABLE stock_log');
    }
}
