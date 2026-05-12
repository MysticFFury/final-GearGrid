<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502111103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stock_movement for staff/admin inventory additions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stock_movement (id INT AUTO_INCREMENT NOT NULL, amount INT NOT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_BB1BC1B54584665A (product_id), INDEX IDX_BB1BC1B5B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE stock_movement ADD CONSTRAINT FK_BB1BC1B54584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_movement ADD CONSTRAINT FK_BB1BC1B5B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_movement DROP FOREIGN KEY FK_BB1BC1B54584665A');
        $this->addSql('ALTER TABLE stock_movement DROP FOREIGN KEY FK_BB1BC1B5B03A8386');
        $this->addSql('DROP TABLE stock_movement');
    }
}
