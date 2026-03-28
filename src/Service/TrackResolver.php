<?php

namespace App\Service;

use App\Exception\InvalidRequestBodyException;
use App\Exception\UnresolvableTrackException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts track parameters from a request body and resolves them to a streamable URL.
 *
 * Centralises the decode → validate → resolve path so play and queue
 * endpoints share the same logic without duplication.
 */
class TrackResolver
{
    public function __construct(private readonly SidecarClient $sidecar) {}

    /**
     * @throws InvalidRequestBodyException if the body is missing or not valid JSON,
     *                                     or if track_id / source are absent.
     * @throws UnresolvableTrackException  if the sidecar cannot produce a stream URL.
     */
    public function resolveFromRequest(Request $request): string
    {
        ['track_id' => $trackId, 'source' => $source] = $this->extractFromRequest($request);

        $url = $this->sidecar->resolve($trackId, $source);

        if ($url === null) {
            throw new UnresolvableTrackException('Could not resolve stream URL.');
        }

        return $url;
    }

    /**
     * Validates the request body and returns track_id and source without resolving.
     *
     * Used when the caller wants to build a proxy URL rather than resolve immediately,
     * e.g. when queuing tracks whose stream URLs would expire before playback.
     *
     * @return array{track_id: string, source: string}
     * @throws InvalidRequestBodyException if the body is missing, invalid JSON, or missing required fields.
     */
    public function extractFromRequest(Request $request): array
    {
        $body = $this->decodeBody($request);

        if (empty($body['track_id']) || empty($body['source'])) {
            throw new InvalidRequestBodyException('track_id and source are required.');
        }

        return ['track_id' => $body['track_id'], 'source' => $body['source']];
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
