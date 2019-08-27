<?php

namespace App\Repository;

use App\Entity\FlexParamValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FlexParamValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method FlexParamValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method FlexParamValue[]    findAll()
 * @method FlexParamValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FlexParamValueRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FlexParamValue::class);
    }
}
