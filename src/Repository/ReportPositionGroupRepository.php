<?php

namespace App\Repository;

use App\Entity\ReportPositionGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class ReportPositionGroupRepository
 * @package App\Repository
 */
class ReportPositionGroupRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReportPositionGroup::class);
    }
}
