<?php

namespace App\Controller;

use App\Service\SidecarClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Internal streaming proxy consumed by MPD, not the browser.
 *
 * Tidal stream URLs are signed and expire within minutes. Storing them
 * directly in MPD's queue means they are stale by the time the track
 * plays. Instead, MPD queues a /stream URL pointing here. On each
 * request this endpoint resolves a fresh URL from the sidecar and
 * redirects MPD to it. MPD's libcurl follows the 302 transparently.
 */
class StreamProxyController extends AbstractController
{
    public function __construct(
        private readonly SidecarClient $sidecar,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/stream/{source}/{trackId}', methods: ['GET'])]
    public function proxy(string $source, string $trackId): Response
    {
        $url = $this->sidecar->resolve($trackId, $source);

        if ($url === null) {
            $this->logger->error('Stream proxy failed to resolve track.', [
                'track_id' => $trackId,
                'source'   => $source,
            ]);

            return new Response('Could not resolve stream URL.', Response::HTTP_BAD_GATEWAY);
        }

        return new RedirectResponse($url);
    }
}
