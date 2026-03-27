<?php

namespace App\Controller;

use App\Enum\MpdCommand;
use App\Exception\InvalidRequestBodyException;
use App\Exception\MpdException;
use App\Exception\UnresolvableTrackException;
use App\Service\MpdPlayer;
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
        private readonly MpdPlayer $mpd,
        private readonly TrackResolver $trackResolver,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/play', methods: ['POST'])]
    public function play(Request $request): JsonResponse
    {
        try {
            $url = $this->trackResolver->resolveFromRequest($request);
            $this->mpd->play($url);
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
            $url = $this->trackResolver->resolveFromRequest($request);
            $this->mpd->addToQueue($url);
        } catch (InvalidRequestBodyException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (UnresolvableTrackException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
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
            $this->mpd->sendCommand(MpdCommand::Pause);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/stop', methods: ['POST'])]
    public function stop(): JsonResponse
    {
        try {
            $this->mpd->sendCommand(MpdCommand::Stop);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/next', methods: ['POST'])]
    public function next(): JsonResponse
    {
        try {
            $this->mpd->sendCommand(MpdCommand::Next);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/previous', methods: ['POST'])]
    public function previous(): JsonResponse
    {
        try {
            $this->mpd->sendCommand(MpdCommand::Previous);
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
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON body.'], 400);
        }

        if (!isset($data['volume'])) {
            return $this->json(['error' => 'volume is required.'], 422);
        }

        try {
            $this->mpd->setVolume((int) $data['volume']);
        } catch (MpdException $e) {
            return $this->json(['error' => $e->getMessage()], 502);
        }

        return $this->json(['status' => 'ok']);
    }
}
