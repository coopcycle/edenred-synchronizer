<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241015093957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__file AS SELECT id, name, sent, errors FROM file');
        $this->addSql('DROP TABLE file');
        $this->addSql('CREATE TABLE file (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, sent BOOLEAN NOT NULL, errors VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO file (id, name, sent, errors, created_at, updated_at) SELECT id, name, sent, errors, current_timestamp, current_timestamp FROM __temp__file');
        $this->addSql('DROP TABLE __temp__file');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__file AS SELECT id, name, sent, errors FROM file');
        $this->addSql('DROP TABLE file');
        $this->addSql('CREATE TABLE file (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, sent BOOLEAN DEFAULT FALSE NOT NULL, errors VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO file (id, name, sent, errors) SELECT id, name, sent, errors FROM __temp__file');
        $this->addSql('DROP TABLE __temp__file');
    }
}
