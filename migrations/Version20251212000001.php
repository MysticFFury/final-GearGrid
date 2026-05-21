<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add email verification fields to user table
 */
final class Version20251212000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification fields (is_verified, verification_token) to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD verification_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_verified');
        $this->addSql('ALTER TABLE user DROP verification_token');
    }
}
