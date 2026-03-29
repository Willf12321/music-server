<?php

namespace App\Dto;

readonly class Album
{
    public function __construct(
        public string $id,
        public string $title,
        public string $artist,
        public int $numTracks,
        public string $source,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            artist: $data['artist'],
            numTracks: (int) $data['num_tracks'],
            source: $data['source'],
        );
    }
}
