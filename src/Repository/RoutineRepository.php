<?php

namespace App\Repository;

use App\Entity\Routine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Routine|null find($id, $lockMode = null, $lockVersion = null)
 * @method Routine|null findOneBy(array $criteria, array $orderBy = null)
 * @method Routine[]    findAll()
 * @method Routine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoutineRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Routine::class);
    }
}
