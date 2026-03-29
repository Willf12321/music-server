<?php

namespace App\Repository;

use App\Entity\RadioStation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RadioStationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RadioStation::class);
    }

    public function findByStreamUrl(string $url): ?RadioStation
    {
        return $this->findOneBy(['streamUrl' => $url]);
    }

    public function save(RadioStation $station): void
    {
        $this->getEntityManager()->persist($station);
        $this->getEntityManager()->flush();
    }

    public function delete(RadioStation $station): void
    {
        $this->getEntityManager()->remove($station);
        $this->getEntityManager()->flush();
    }
}
