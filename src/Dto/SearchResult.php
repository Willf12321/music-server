<?php

namespace App\Dto;

readonly class SearchResult
{
    /**
     * @param Track[] $tracks
     * @param Album[] $albums
     */
    public function __construct(
        public array $tracks,
        public array $albums,
    ) {}
}
