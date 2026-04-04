<?php

namespace App\Service;

use App\Entity\RadioStation;
use App\Repository\RadioStationRepository;

/**
 * Finds radio stations from the database whose name matches a search query.
 *
 * All stations live in the database — there is no separate hardcoded catalogue.
 * Users manage their stations via the /radio section.
 */
class RadioSearcher
{
    public function __construct(private readonly RadioStationRepository $repository) {}

    /**
     * Returns stations whose name contains the query (case-insensitive).
     *
     * @return RadioStation[]
     */
    public function search(string $query): array
    {
        return $this->repository->findByNameContaining($query);
    }
}
