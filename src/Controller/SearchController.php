<?php

namespace App\Controller;

use App\Service\TrackSearcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Intentionally thin — all logic lives in TrackSearcher.
 *
 * The controller's only job is to read from the request, delegate to the
 * searcher, and hand results to the template.
 */
class SearchController extends AbstractController
{
    public function __construct(private readonly TrackSearcher $trackSearcher) {}

    #[Route('/', name: 'search_index')]
    #[Route('/search', name: 'search')]
    public function index(Request $request): Response
    {
        $query = trim($request->query->getString('q'));

        if ($query === '') {
            return $this->render('search/index.html.twig', [
                'query' => '',
                'results' => [],
                'searched' => false,
            ]);
        }

        $results = $this->trackSearcher->search($query);

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
            'searched' => true,
        ]);
    }
}
