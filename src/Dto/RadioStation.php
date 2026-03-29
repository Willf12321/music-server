<?php

namespace App\Dto;

readonly class RadioStation
{
    public function __construct(
        public string $id,
        public string $name,
        public string $streamUrl,
        public string $genre,
    ) {}
}
