<?php

namespace App\Repository;

use App\Entity\RoutineTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method RoutineTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoutineTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoutineTemplate[]    findAll()
 * @method RoutineTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoutineTemplateRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RoutineTemplate::class);
    }
}
