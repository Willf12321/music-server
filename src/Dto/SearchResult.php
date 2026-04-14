<?php

namespace App\Dto;

use App\Entity\RadioStation;

readonly class SearchResult
{
    /**
     * @param Track[]         $tracks
     * @param Album[]         $albums
     * @param RadioStation[]  $stations
     * @param TidalUser[]     $users
     */
    public function __construct(
        public array $tracks,
        public array $albums,
        public array $stations = [],
        public array $users = [],
    ) {}
}
