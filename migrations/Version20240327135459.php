<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240327135459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE merchant (siret VARCHAR(20) NOT NULL, add_info VARCHAR(255) DEFAULT NULL, merchant_id VARCHAR(255) DEFAULT NULL, accepts_trcard BOOLEAN DEFAULT NULL, address VARCHAR(100) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(5) DEFAULT NULL, PRIMARY KEY(siret))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE merchant');
    }
}
