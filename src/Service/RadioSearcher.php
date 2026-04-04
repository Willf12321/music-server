<?php

namespace App\Service;

use App\Dto\DiscoveredStation;
use App\Repository\RadioStationRepository;

/**
 * Searches a curated discovery catalogue of well-known internet radio stations.
 *
 * The catalogue is hardcoded here — it represents a set of broadly interesting
 * stations that users can browse and save to their own library. Saved stations
 * are stored in the database and managed via the /radio section.
 *
 * Search results are annotated with whether each station has already been
 * saved, so the UI can show an "Added" badge or an "Add" button accordingly.
 */
class RadioSearcher
{
    /** @var array<array{name: string, streamUrl: string, genre: string}> */
    private array $catalogue;

    public function __construct(private readonly RadioStationRepository $repository)
    {
        $this->catalogue = [
            ['name' => 'NTS Radio 1',  'streamUrl' => 'https://stream-relay-geo.ntslive.net/stream',  'genre' => 'Various'],
            ['name' => 'NTS Radio 2',  'streamUrl' => 'https://stream-relay-geo.ntslive.net/stream2', 'genre' => 'Various'],
            ['name' => 'BBC 6 Music',  'streamUrl' => 'hls+http://a.files.bbci.co.uk/media/live/manifesto/audio/simulcast/hls/uk/sbr_high/ak/bbc_6music.m3u8',     'genre' => 'Alternative'],
            ['name' => 'BBC Radio 1',  'streamUrl' => 'hls+http://a.files.bbci.co.uk/media/live/manifesto/audio/simulcast/hls/uk/sbr_high/ak/bbc_radio_one.m3u8',   'genre' => 'Pop'],
            ['name' => 'BBC Radio 2',  'streamUrl' => 'hls+http://a.files.bbci.co.uk/media/live/manifesto/audio/simulcast/hls/uk/sbr_high/ak/bbc_radio_two.m3u8',   'genre' => 'Easy Listening'],
        ];
    }

    /**
     * Returns catalogue stations whose name contains the query (case-insensitive),
     * each annotated with whether it has been saved to the library.
     *
     * @return DiscoveredStation[]
     */
    public function search(string $query): array
    {
        $lower    = strtolower($query);
        $matching = array_filter(
            $this->catalogue,
            fn(array $s) => $query === '' || str_contains(strtolower($s['name']), $lower),
        );

        return array_values(array_map(function (array $s): DiscoveredStation {
            $saved = $this->repository->findByStreamUrl($s['streamUrl']);

            return new DiscoveredStation(
                name:      $s['name'],
                streamUrl: $s['streamUrl'],
                genre:     $s['genre'],
                isAdded:   $saved !== null,
                savedId:   $saved?->getId(),
            );
        }, $matching));
    }
}
