<?php

namespace App\Controller;

use App\Exception\MpdException;
use App\Service\MpdService;
use App\Service\SidecarClient;
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
        private readonly MpdService $mpd,
        private readonly SidecarClient $sidecar,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/play', methods: ['POST'])]
    public function play(Request $request): JsonResponse
    {
        $body = $this->decodeBody($request);

        if ($body === null) {
            return $this->json(['error' => 'Invalid JSON body.'], 400);
        }

        if (empty($body['track_id']) || empty($body['source'])) {
            return $this->json(['error' => 'track_id and source are required.'], 422);
        }

        $url = $this->sidecar->resolve($body['track_id'], $body['source']);

        if ($url === null) {
            return $this->json(['error' => 'Could not resolve stream URL.'], 502);
        }

        try {
            $this->mpd->play($url);
        } catch (MpdException $e) {
            $this->logger->error('MPD play failed.', ['error' => $e->getMessage()]);

            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/queue', methods: ['POST'])]
    public function queue(Request $request): JsonResponse
    {
        $body = $this->decodeBody($request);

        if ($body === null) {
            return $this->json(['error' => 'Invalid JSON body.'], 400);
        }

        if (empty($body['track_id']) || empty($body['source'])) {
            return $this->json(['error' => 'track_id and source are required.'], 422);
        }

        $url = $this->sidecar->resolve($body['track_id'], $body['source']);

        if ($url === null) {
            return $this->json(['error' => 'Could not resolve stream URL.'], 502);
        }

        try {
            $this->mpd->addToQueue($url);
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
            $this->mpd->pause();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/stop', methods: ['POST'])]
    public function stop(): JsonResponse
    {
        try {
            $this->mpd->stop();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/next', methods: ['POST'])]
    public function next(): JsonResponse
    {
        try {
            $this->mpd->next();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/previous', methods: ['POST'])]
    public function previous(): JsonResponse
    {
        try {
            $this->mpd->previous();
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        try {
            return $this->json($this->mpd->getStatus());
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }
    }

    #[Route('/queue', methods: ['GET'])]
    public function getQueue(): JsonResponse
    {
        try {
            return $this->json($this->mpd->getQueue());
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }
    }

    #[Route('/volume', methods: ['POST'])]
    public function volume(Request $request): JsonResponse
    {
        $body = $this->decodeBody($request);

        if ($body === null) {
            return $this->json(['error' => 'Invalid JSON body.'], 400);
        }

        if (!isset($body['volume'])) {
            return $this->json(['error' => 'volume is required.'], 422);
        }

        try {
            $this->mpd->setVolume((int) $body['volume']);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    private function decodeBody(Request $request): ?array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }
}
