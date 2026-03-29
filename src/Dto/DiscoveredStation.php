<?php

namespace App\Dto;

/**
 * A station from the discovery catalogue, annotated with whether it has
 * been saved to the user's library.
 */
readonly class DiscoveredStation
{
    public function __construct(
        public string $name,
        public string $streamUrl,
        public string $genre,
        public bool $isAdded,
        public ?int $savedId = null,
    ) {}
}
