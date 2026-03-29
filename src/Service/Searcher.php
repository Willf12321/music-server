<?php

namespace App\Service;

use App\Dto\Album;
use App\Dto\SearchResult;
use App\Dto\Track;

/**
 * Sits between the controller and SidecarClient.
 *
 * Maps raw sidecar responses into typed DTOs and returns a SearchResult
 * grouping tracks and albums together. Source-merging logic (e.g. YouTube
 * fallback) will live here when implemented.
 */
class Searcher
{
    public function __construct(private readonly SidecarClient $sidecarClient) {}

    public function search(string $query): SearchResult
    {
        $raw = $this->sidecarClient->search($query);

        $tracks = array_map(
            static fn(array $t) => Track::fromArray($t),
            $raw['tracks'] ?? [],
        );

        $albums = array_map(
            static fn(array $a) => Album::fromArray($a),
            $raw['albums'] ?? [],
        );

        return new SearchResult($tracks, $albums);
    }

    /** @return Track[] */
    public function getAlbumTracks(string $albumId, string $source): array
    {
        $raw = $this->sidecarClient->getAlbumTracks($albumId, $source);

        return array_map(static fn(array $t) => Track::fromArray($t), $raw);
    }
}
