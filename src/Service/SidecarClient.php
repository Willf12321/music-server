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
                'query' => ['q' => $query],
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

    public function searchUsers(string $query): array
    {
        if ($query === '') {
            throw new \InvalidArgumentException('Search query must not be empty.');
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/users/search', [
                'query' => ['q' => $query],
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Sidecar user search request failed.', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch public playlists for a Tidal user by their numeric ID.
     *
     * Tidal user IDs are integers. Rejecting non-numeric values here prevents
     * path traversal from a malformed route parameter reaching the sidecar.
     */
    public function getUserPlaylists(string $userId): array
    {
        if (!ctype_digit($userId)) {
            throw new \InvalidArgumentException("Invalid Tidal user ID: {$userId}");
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . "/users/{$userId}/playlists");

            if ($response->getStatusCode() === 404) {
                return [];
            }

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Sidecar user playlists request failed.', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch tracks for a Tidal playlist by its UUID.
     *
     * Tidal playlist IDs are UUIDs. Rejecting values that don't match the
     * UUID format prevents malformed route parameters reaching the sidecar.
     */
    public function getPlaylistTracks(string $playlistId): array
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $playlistId)) {
            throw new \InvalidArgumentException("Invalid Tidal playlist ID: {$playlistId}");
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . "/playlists/{$playlistId}/tracks");

            if ($response->getStatusCode() === 404) {
                return [];
            }

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Sidecar playlist tracks request failed.', [
                'playlist_id' => $playlistId,
                'error'       => $e->getMessage(),
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
