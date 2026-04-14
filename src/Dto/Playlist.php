<?php

namespace App\Dto;

/**
 * Represents a Tidal playlist belonging to a user.
 *
 * Tidal playlist IDs are UUIDs. num_tracks comes from the playlist metadata
 * rather than counting items — it reflects the true size including any
 * video-only tracks that the sidecar filters out during track fetching.
 */
readonly class Playlist
{
    public function __construct(
        public string $id,
        public string $name,
        public int $numTracks,
        public string $description,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            numTracks: (int) $data['num_tracks'],
            description: $data['description'] ?? '',
        );
    }
}
