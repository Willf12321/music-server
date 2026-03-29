<?php

namespace App\Dto;

readonly class SearchResult
{
    /**
     * @param Track[]        $tracks
     * @param Album[]        $albums
     * @param RadioStation[] $stations
     */
    public function __construct(
        public array $tracks,
        public array $albums,
        public array $stations = [],
    ) {}
}
