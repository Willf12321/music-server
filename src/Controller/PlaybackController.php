<?php

namespace App\Controller;

use App\Exception\InvalidRequestBodyException;
use App\Exception\MpdException;
use App\Exception\UnresolvableTrackException;
use App\Service\MpdPlayer\MpdDriver;
use App\Service\MpdPlayer\MpdInspector;
use App\Service\MpdPlayer\MpdQueuer;
use App\Service\TrackMetadataStorer;
use App\Service\TrackResolver;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * JSON API endpoints consumed by the frontend via fetch().
 *
 * This controller never renders HTML — all responses are JSON. The UI calls
 * these endpoints directly and updates the page without a full reload.
 */
#[Route('/playback')]
class PlaybackController extends AbstractController
{
    public function __construct(
        private readonly MpdQueuer $queuer,
        private readonly MpdDriver $driver,
        private readonly MpdInspector $inspector,
        private readonly TrackResolver $trackResolver,
        private readonly TrackMetadataStorer $metadataStorer,
        private readonly LoggerInterface $logger,
        private readonly string $streamBaseUrl = 'http://nginx',
    ) {}

    #[Route('/play', methods: ['POST'])]
    public function play(Request $request): JsonResponse
    {
        try {
            $track = $this->trackResolver->extractFromRequest($request);
            $url   = $this->trackResolver->resolve($track['track_id'], $track['source']);
            $this->queuer->play($url);
            $this->metadataStorer->store($track['source'], $track['track_id'], $track);
        } catch (InvalidRequestBodyException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (UnresolvableTrackException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        } catch (MpdException $e) {
            $this->logger->error('MPD play failed.', ['error' => $e->getMessage()]);

            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/queue', methods: ['POST'])]
    public function queue(Request $request): JsonResponse
    {
        try {
            $track = $this->trackResolver->extractFromRequest($request);

            // Use a proxy URL rather than resolving the stream URL immediately.
            // Tidal URLs are signed and expire within minutes — by the time MPD
            // auto-advances or the user presses next, a pre-resolved URL is stale.
            // The /stream endpoint resolves a fresh URL on demand when MPD fetches it.
            $proxyUrl = "{$this->streamBaseUrl}/stream/{$track['source']}/{$track['track_id']}";
            $this->queuer->addToQueue($proxyUrl);
            $this->metadataStorer->store($track['source'], $track['track_id'], $track);
        } catch (InvalidRequestBodyException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (MpdException $e) {
            $this->logger->error('MPD queue failed.', ['error' => $e->getMessage()]);

            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/pause', methods: ['POST'])]
    public function pause(): JsonResponse
    {
        try {
            $this->driver->pause();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/stop', methods: ['POST'])]
    public function stop(): JsonResponse
    {
        try {
            $this->driver->stop();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/next', methods: ['POST'])]
    public function next(): JsonResponse
    {
        try {
            $this->driver->next();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/previous', methods: ['POST'])]
    public function previous(): JsonResponse
    {
        try {
            $this->driver->previous();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        try {
            $status = $this->inspector->getStatus();

            // Proxy URLs carry no file tags, so MPD's currentsong has no title/artist/album.
            // Parse the source + track ID from the URL and look up what we stored at queue time.
            if (isset($status['file']) && preg_match('#/stream/([^/]+)/([^/?]+)#', $status['file'], $m)) {
                $metadata = $this->metadataStorer->retrieve($m[1], $m[2]);
                if ($metadata !== null) {
                    $status = array_merge($status, $metadata);
                }
            }

            return $this->json($status);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }
    }

    #[Route('/queue', methods: ['GET'])]
    public function getQueue(): JsonResponse
    {
        try {
            return $this->json($this->inspector->getQueue());
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }
    }

    #[Route('/clear-queue', methods: ['POST'])]
    public function clearQueue(): JsonResponse
    {
        try {
            $this->queuer->clearQueue();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/seek', methods: ['POST'])]
    public function seek(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON body.'], 400);
        }

        if (!isset($data['position'])) {
            return $this->json(['error' => 'position is required.'], 422);
        }

        try {
            $this->driver->seek((int) $data['position']);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/volume', methods: ['POST'])]
    public function volume(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON body.'], 400);
        }

        if (!isset($data['volume'])) {
            return $this->json(['error' => 'volume is required.'], 422);
        }

        try {
            $this->driver->setVolume((int) $data['volume']);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }
}
