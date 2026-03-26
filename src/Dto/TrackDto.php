<?php

namespace App\Dto;

/**
 * Represents a single track returned from the sidecar search.
 *
 * Duration is stored in seconds because that is what MPD expects when
 * adding a track to its queue. Display formatting to mm:ss happens here
 * rather than at call sites so the conversion logic lives in one place.
 */
readonly class TrackDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $artist,
        public string $album,
        public int $durationSeconds,
        public string $source,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            artist: $data['artist'],
            album: $data['album'],
            durationSeconds: (int) $data['duration_seconds'],
            source: $data['source'],
        );
    }

    public function formattedDuration(): string
    {
        $minutes = intdiv($this->durationSeconds, 60);
        $seconds = $this->durationSeconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
