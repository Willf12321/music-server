<?php

namespace App\Controller;

use App\Service\UserSearcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Intentionally thin — all logic lives in UserSearcher.
 */
class UserController extends AbstractController
{
    public function __construct(private readonly UserSearcher $userSearcher) {}

    #[Route('/users/{id}/playlists', methods: ['GET'])]
    public function playlists(string $id): JsonResponse
    {
        $playlists = $this->userSearcher->getPlaylists($id);

        return $this->json(array_map(static fn($p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'num_tracks' => $p->numTracks,
            'description' => $p->description,
        ], $playlists));
    }

    #[Route('/playlists/{id}/tracks', methods: ['GET'])]
    public function playlistTracks(string $id): JsonResponse
    {
        $tracks = $this->userSearcher->getPlaylistTracks($id);

        return $this->json(array_map(static fn($t) => [
            'id'       => $t->id,
            'title'    => $t->title,
            'artist'   => $t->artist,
            'album'    => $t->album,
            'duration' => $t->formattedDuration(),
            'source'   => $t->source,
        ], $tracks));
    }
}
