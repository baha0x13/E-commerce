<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250531234745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` CHANGE verification_token verification_token VARCHAR(64) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD is_deleted TINYINT(1) NOT NULL, CHANGE photo photo VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE verification_token verification_token VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` CHANGE verification_token verification_token VARCHAR(64) DEFAULT 'NULL'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP is_deleted, CHANGE photo photo VARCHAR(255) DEFAULT 'NULL'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE verification_token verification_token VARCHAR(255) DEFAULT 'NULL'
        SQL);
    }
}
