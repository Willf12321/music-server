<?php

namespace App\Controller;

use App\Service\PlaylistBrowser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Intentionally thin — all logic lives in PlaylistBrowser.
 */
class UserController extends AbstractController
{
    public function __construct(private readonly PlaylistBrowser $playlistBrowser) {}

    #[Route('/me/playlists', methods: ['GET'])]
    public function myPlaylists(): JsonResponse
    {
        $playlists = $this->playlistBrowser->getMyPlaylists();

        return $this->json(array_map(static fn($p) => [
            'id'          => $p->id,
            'name'        => $p->name,
            'num_tracks'  => $p->numTracks,
            'description' => $p->description,
        ], $playlists));
    }

    #[Route('/playlists/{id}/tracks', methods: ['GET'])]
    public function playlistTracks(string $id): JsonResponse
    {
        $tracks = $this->playlistBrowser->getPlaylistTracks($id);

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
