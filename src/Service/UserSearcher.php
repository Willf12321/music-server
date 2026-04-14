<?php

namespace App\Service;

use App\Dto\Playlist;
use App\Dto\TidalUser;
use App\Dto\Track;

/**
 * Handles Tidal user search and playlist retrieval.
 *
 * Kept separate from Searcher so each class stays focused on one resource
 * type — the same reason RadioSearcher is its own class rather than a method
 * on Searcher.
 */
class UserSearcher
{
    public function __construct(private readonly SidecarClient $sidecarClient) {}

    /** @return TidalUser[] */
    public function search(string $query): array
    {
        $raw = $this->sidecarClient->searchUsers($query);

        return array_map(static fn(array $u) => TidalUser::fromArray($u), $raw);
    }

    /**
     * Returns the user's public playlists, or an empty array if the user
     * has none or the ID validation in SidecarClient rejects it.
     *
     * @return Playlist[]
     */
    public function getPlaylists(string $userId): array
    {
        try {
            $raw = $this->sidecarClient->getUserPlaylists($userId);
        } catch (\InvalidArgumentException) {
            return [];
        }

        return array_map(static fn(array $p) => Playlist::fromArray($p), $raw);
    }

    /**
     * Returns all audio tracks in a playlist. An empty array means the
     * playlist exists but contains only videos, or the ID was invalid.
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
