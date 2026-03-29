<?php

namespace App\Entity;

use App\Repository\RadioStationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RadioStationRepository::class)]
#[ORM\Table(name: 'radio_stations')]
class RadioStation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 1000, unique: true)]
    private string $streamUrl;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genre;

    public function __construct(string $name, string $streamUrl, ?string $genre = null)
    {
        $this->name      = $name;
        $this->streamUrl = $streamUrl;
        $this->genre     = $genre;
    }

    public function getId(): int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getStreamUrl(): string { return $this->streamUrl; }
    public function setStreamUrl(string $streamUrl): void { $this->streamUrl = $streamUrl; }

    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $genre): void { $this->genre = $genre; }
}
