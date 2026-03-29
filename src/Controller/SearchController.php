<?php

namespace App\Controller;

use App\Service\Searcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Intentionally thin — all logic lives in Searcher.
 *
 * The controller's only job is to read from the request, delegate to the
 * searcher, and hand results to the template.
 */
class SearchController extends AbstractController
{
    public function __construct(private readonly Searcher $searcher) {}

    #[Route('/', name: 'search_index')]
    #[Route('/search', name: 'search')]
    public function index(Request $request): Response
    {
        $query = trim($request->query->getString('q'));

        if ($query === '') {
            return $this->render('search/index.html.twig', [
                'query'    => '',
                'result'   => null,
                'searched' => false,
            ]);
        }

        return $this->render('search/index.html.twig', [
            'query'    => $query,
            'result'   => $this->searcher->search($query),
            'searched' => true,
        ]);
    }

    #[Route('/search/album/{id}/tracks', methods: ['GET'])]
    public function albumTracks(string $id, Request $request): JsonResponse
    {
        $source = $request->query->getString('source', 'tidal');
        $tracks = $this->searcher->getAlbumTracks($id, $source);

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
