<?php

namespace App\Service;

use App\Dto\Track;

/**
 * Sits between the controller and SidecarClient.
 *
 * Responsible for mapping raw sidecar responses into TrackDto objects.
 * This is also where source-merging logic will live when YouTube fallback
 * is added — Tidal results and YouTube results will be combined here before
 * being returned to the controller.
 */
class TrackSearcher
{
    public function __construct(
        private readonly SidecarClient $sidecarClient
    ) {}

    /**
     * @return Track[]
     */
    public function search(string $query): array
    {
        $raw = $this->sidecarClient->search($query);

        return array_map(
            static fn(array $track) => Track::fromArray($track),
            $raw,
        );
    }
}
