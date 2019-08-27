<?php

namespace App\Repository;

use App\Entity\CostForecastWeekDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CostForecastWeekDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method CostForecastWeekDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method CostForecastWeekDay[]    findAll()
 * @method CostForecastWeekDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CostForecastWeekDayRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CostForecastWeekDay::class);
    }
}
