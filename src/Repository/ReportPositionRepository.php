<?php

namespace App\Repository;

use App\Entity\Report;
use App\Entity\ReportPosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReportPosition|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReportPosition|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReportPosition[]    findAll()
 * @method ReportPosition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportPositionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReportPosition::class);
    }

    /**
     * @param Report $report
     * @return mixed
     */
    public function getMaxReportPosition(Report $report)
    {
        return $this
            ->createQueryBuilder('reportPosition')
            ->leftJoin('reportPosition.report', 'report')
            ->where('report = :report')
            ->andWhere('reportPosition.deletedAt IS NULL')
            ->setParameter('report', $report)
            ->orderBy('reportPosition.id', 'DESC' )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
