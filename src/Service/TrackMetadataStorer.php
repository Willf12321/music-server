<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Stores and retrieves track metadata in Redis, keyed by source + track ID.
 *
 * MPD streams tracks via proxy URLs that carry no file tags, so title/artist/album
 * would otherwise be unavailable in currentsong responses. We store the metadata
 * at queue/play time and merge it back into the status response.
 */
class TrackMetadataStorer
{
    private const TTL = 86400; // 24 hours — long enough to survive a full queue session

    public function __construct(private readonly CacheItemPoolInterface $cache) {}

    /** @param array{title?: string, artist?: string, album?: string, date?: string} $metadata */
    public function store(string $source, string $trackId, array $metadata): void
    {
        $item = $this->cache->getItem($this->key($source, $trackId));
        $item->set(array_filter([
            'title'  => $metadata['title'] ?? null,
            'artist' => $metadata['artist'] ?? null,
            'album'  => $metadata['album'] ?? null,
            'date'   => $metadata['date'] ?? null,
        ]));
        $item->expiresAfter(self::TTL);

        $this->cache->save($item);
    }

    /** @return array{title?: string, artist?: string, album?: string, date?: string}|null */
    public function retrieve(string $source, string $trackId): ?array
    {
        $item = $this->cache->getItem($this->key($source, $trackId));

        return $item->isHit() ? ($item->get() ?: null) : null;
    }

    private function key(string $source, string $trackId): string
    {
        // Cache keys may not contain {, }, (, ), /, \, @, : — replace with dots.
        return 'track.' . preg_replace('/[{}()\/\\\\@:]/', '.', $source . '.' . $trackId);
    }
}
