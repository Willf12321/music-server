<?php

namespace App\Service;

use App\Dto\Playlist;
use App\Dto\Track;

/**
 * Retrieves Tidal playlists and their tracks via the sidecar.
 *
 * Kept separate from Searcher so each class stays focused on one resource
 * type — the same reason RadioSearcher is its own class rather than a method
 * on Searcher.
 */
class PlaylistBrowser
{
    public function __construct(private readonly SidecarClient $sidecarClient) {}

    /** @return Playlist[] */
    public function getMyPlaylists(): array
    {
        $raw = $this->sidecarClient->getMyPlaylists();

        return array_map(static fn(array $p) => Playlist::fromArray($p), $raw);
    }

    /**
     * Returns all audio tracks in a playlist, or an empty array if the ID
     * validation in SidecarClient rejects it.
     *
     * @return Track[]
     */
    public function getPlaylistTracks(string $playlistId): array
    {
        try {
            $raw = $this->sidecarClient->getPlaylistTracks($playlistId);
        } catch (\InvalidArgumentException) {
            return [];
        }

        return array_map(static fn(array $t) => Track::fromArray($t), $raw);
    }
}
