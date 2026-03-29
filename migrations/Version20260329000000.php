<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260329000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create radio_stations table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE radio_stations (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            stream_url VARCHAR(1000) NOT NULL,
            genre VARCHAR(100) DEFAULT NULL,
            UNIQUE INDEX UNIQ_stream_url (stream_url(255)),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE radio_stations');
    }
}
