<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to make password field nullable for OAuth2 (Google) authentication
 */
final class Version20251212000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make password field nullable to support OAuth2 (Google) authentication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) NOT NULL');
    }
}
