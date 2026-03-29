<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Single point of contact between Symfony and the Python sidecar service.
 *
 * All communication with the sidecar goes through here. Nothing else in the
 * application should make HTTP calls to the sidecar directly.
 */
class SidecarClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $baseUrl = 'http://python-sidecar:8001',
    ) {}

    public function search(string $query): array
    {
        if ($query === '') {
            throw new \InvalidArgumentException('Search query must not be empty.');
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/search', [
                'query' => ['q' => $query, 'source' => 'auto'],
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Sidecar search request failed.', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getAlbumTracks(string $albumId, string $source): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->baseUrl . "/album/{$albumId}/tracks",
                ['query' => ['source' => $source]],
            );

            if ($response->getStatusCode() === 404) {
                return [];
            }

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Sidecar album tracks request failed.', [
                'album_id' => $albumId,
                'source'   => $source,
                'error'    => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function resolve(string $trackId, string $source): ?string
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/resolve', [
                'json' => ['track_id' => $trackId, 'source' => $source],
            ]);

            if ($response->getStatusCode() === 404) {
                return null;
            }

            return $response->toArray()['url'] ?? null;
        } catch (\Throwable $e) {
            $this->logger->error('Sidecar resolve request failed.', [
                'track_id' => $trackId,
                'source'   => $source,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

}
