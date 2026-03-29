<?php

namespace App\Service;

use App\Exception\InvalidRequestBodyException;
use App\Exception\UnresolvableTrackException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts track parameters from a request body, and resolves track IDs to stream URLs.
 *
 * Centralises the decode → validate path so play and queue endpoints share
 * the same request-parsing logic without duplication.
 */
class TrackResolver
{
    public function __construct(private readonly SidecarClient $sidecar) {}

    /**
     * Validates the request body and returns track_id, source, and optional metadata fields.
     *
     * @return array{track_id: string, source: string, title: ?string, artist: ?string, album: ?string, date: ?string}
     * @throws InvalidRequestBodyException if the body is missing, invalid JSON, or missing required fields.
     */
    public function extractFromRequest(Request $request): array
    {
        $body = $this->decodeBody($request);

        if (empty($body['track_id']) || empty($body['source'])) {
            throw new InvalidRequestBodyException('track_id and source are required.');
        }

        return [
            'track_id' => $body['track_id'],
            'source'   => $body['source'],
            'title'    => $body['title'] ?? null,
            'artist'   => $body['artist'] ?? null,
            'album'    => $body['album'] ?? null,
            'date'     => $body['date'] ?? null,
        ];
    }

    /** @throws UnresolvableTrackException if the sidecar cannot produce a stream URL. */
    public function resolve(string $trackId, string $source): string
    {
        $url = $this->sidecar->resolve($trackId, $source);

        if ($url === null) {
            throw new UnresolvableTrackException('Could not resolve stream URL.');
        }

        return $url;
    }

    private function decodeBody(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidRequestBodyException('Invalid JSON body.');
        }

        return $data;
    }
}
