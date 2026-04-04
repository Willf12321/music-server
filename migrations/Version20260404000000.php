<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed default radio stations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO radio_stations (name, stream_url, genre) VALUES
            ('NTS Radio 1', 'https://stream-relay-geo.ntslive.net/stream', 'Various'),
            ('NTS Radio 2', 'https://stream-relay-geo.ntslive.net/stream2', 'Various'),
            ('BBC 6 Music', 'hls+http://as-hls-ww-live.akamaized.net/pool_81827798/live/ww/bbc_6music/bbc_6music.isml/bbc_6music-audio=320000.norewind.m3u8', 'Alternative'),
            ('BBC Radio 1', 'hls+http://as-hls-ww-live.akamaized.net/pool_01505109/live/ww/bbc_radio_one/bbc_radio_one.isml/bbc_radio_one-audio=320000.norewind.m3u8', 'Pop'),
            ('BBC Radio 2', 'hls+http://as-hls-ww-live.akamaized.net/pool_74208725/live/ww/bbc_radio_two/bbc_radio_two.isml/bbc_radio_two-audio=320000.norewind.m3u8', 'Easy Listening')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM radio_stations WHERE name IN (
            'NTS Radio 1', 'NTS Radio 2', 'BBC 6 Music', 'BBC Radio 1', 'BBC Radio 2'
        )");
    }
}
