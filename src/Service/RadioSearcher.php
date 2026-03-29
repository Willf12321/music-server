<?php

namespace App\Service;

use App\Dto\RadioStation;

/**
 * Registry of known internet radio stations.
 *
 * Stations are hardcoded here — the list is short and stable enough that a
 * database or config file would add complexity without real benefit. Add new
 * stations by extending the array in the constructor.
 */
class RadioSearcher
{
    /** @var RadioStation[] */
    private array $stations;

    public function __construct()
    {
        $this->stations = [
            new RadioStation('nts-1',    'NTS Radio 1', 'https://stream-relay-geo.ntslive.net/stream',  'Various'),
            new RadioStation('nts-2',    'NTS Radio 2', 'https://stream-relay-geo.ntslive.net/stream2', 'Various'),
            new RadioStation('bbc-6',    'BBC 6 Music',  'https://as-hls-ww.live.cf.md.bbci.co.uk/pool_904/live/ww/bbc_6music/bbc_6music.isml/bbc_6music-audio%3d96000.norewind.m3u8', 'Alternative'),
            new RadioStation('bbc-r1',   'BBC Radio 1',  'https://as-hls-ww.live.cf.md.bbci.co.uk/pool_904/live/ww/bbc_radio_one/bbc_radio_one.isml/bbc_radio_one-audio%3d96000.norewind.m3u8', 'Pop'),
            new RadioStation('bbc-r2',   'BBC Radio 2',  'https://as-hls-ww.live.cf.md.bbci.co.uk/pool_904/live/ww/bbc_radio_two/bbc_radio_two.isml/bbc_radio_two-audio%3d96000.norewind.m3u8', 'Easy Listening'),
        ];
    }

    /**
     * Returns all stations whose name contains the query (case-insensitive).
     * Returns all stations when the query is empty.
     *
     * @return RadioStation[]
     */
    public function search(string $query): array
    {
        if ($query === '') {
            return $this->stations;
        }

        $lower = strtolower($query);

        return array_values(array_filter(
            $this->stations,
            fn(RadioStation $s) => str_contains(strtolower($s->name), $lower),
        ));
    }

    public function findById(string $id): ?RadioStation
    {
        foreach ($this->stations as $station) {
            if ($station->id === $id) {
                return $station;
            }
        }

        return null;
    }

    public function findByStreamUrl(string $url): ?RadioStation
    {
        foreach ($this->stations as $station) {
            if ($station->streamUrl === $url) {
                return $station;
            }
        }

        return null;
    }
}
