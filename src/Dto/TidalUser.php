<?php

namespace App\Dto;

/**
 * Represents a Tidal user profile returned from a user search.
 *
 * The ID is kept as a string even though Tidal uses integers internally.
 * This matches the pattern of Track and Album so callers never need to
 * care about the underlying type.
 */
readonly class TidalUser
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }
}
