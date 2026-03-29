<?php

namespace App\Controller;

use App\Entity\RadioStation;
use App\Repository\RadioStationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/radio')]
class RadioController extends AbstractController
{
    public function __construct(private readonly RadioStationRepository $repository) {}

    #[Route('', name: 'radio_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('radio/index.html.twig', [
            'stations' => $this->repository->findAll(),
        ]);
    }

    #[Route('/new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('radio/form.html.twig', [
            'station' => null,
            'action'  => '/radio',
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $name      = trim($request->request->getString('name'));
        $streamUrl = trim($request->request->getString('stream_url'));
        $genre     = trim($request->request->getString('genre')) ?: null;

        if ($name === '' || $streamUrl === '') {
            return $this->render('radio/form.html.twig', [
                'station' => null,
                'action'  => '/radio',
                'error'   => 'Name and stream URL are required.',
            ]);
        }

        $this->repository->save(new RadioStation($name, $streamUrl, $genre));

        return $this->redirectToRoute('radio_index');
    }

    #[Route('/{id}/edit', methods: ['GET'])]
    public function editForm(int $id): Response
    {
        $station = $this->repository->find($id);

        if ($station === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('radio/form.html.twig', [
            'station' => $station,
            'action'  => "/radio/{$id}",
        ]);
    }

    #[Route('/{id}', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $station = $this->repository->find($id);

        if ($station === null) {
            throw $this->createNotFoundException();
        }

        $name      = trim($request->request->getString('name'));
        $streamUrl = trim($request->request->getString('stream_url'));
        $genre     = trim($request->request->getString('genre')) ?: null;

        if ($name === '' || $streamUrl === '') {
            return $this->render('radio/form.html.twig', [
                'station' => $station,
                'action'  => "/radio/{$id}",
                'error'   => 'Name and stream URL are required.',
            ]);
        }

        $station->setName($name);
        $station->setStreamUrl($streamUrl);
        $station->setGenre($genre);
        $this->repository->save($station);

        return $this->redirectToRoute('radio_index');
    }

    #[Route('/{id}/delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $station = $this->repository->find($id);

        if ($station !== null) {
            $this->repository->delete($station);
        }

        return $this->redirectToRoute('radio_index');
    }
}
