<?php

namespace App\Repository;

use App\Entity\AccountingPosition;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReportPositionValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReportPositionValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReportPositionValue[]    findAll()
 * @method ReportPositionValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportPositionValueRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReportPositionValue::class);
    }

    /**
     * @param ReportPosition $reportPosition
     * @return ReportPositionValue|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneReportPositionValueByReportPosition(ReportPosition $reportPosition): ?ReportPositionValue
    {
        return $this->createQueryBuilder('reportPositionValue')
            ->andWhere('reportPositionValue.reportPosition = :reportPosition')
            ->andWhere('reportPositionValue.deletedAt IS NULL')
            ->setParameter('reportPosition', $reportPosition->getId())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param AccountingPosition $accountingPosition
     * @param Report $report
     * @return array
     */
    public function findReportPositionValuesByAccountingPosition(
        AccountingPosition $accountingPosition,
        Report $report
    ): array {
        return $this->createQueryBuilder('reportPositionValue')
            ->leftJoin('reportPositionValue.reportPosition', 'reportPosition')
            ->leftJoin('reportPosition.accountingPosition', 'accountingPosition')
            ->andWhere('accountingPosition.id = :accountingPositionId')
            ->andWhere('reportPosition.report = :reportPositionReportId')
            ->andWhere('reportPositionValue.deletedAt IS NULL')
            ->andWhere('reportPosition.deletedAt IS NULL')
            ->setParameter('accountingPositionId', $accountingPosition->getId())
            ->setParameter('reportPositionReportId', $report->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array $ids
     * @param string $reportType
     * @return mixed
     */
    public function getReportTotalAmount(array $ids, string $reportType)
    {
        $conn = $this->_em->getConnection();
        return $conn->createQueryBuilder()
            ->select("SUM(CAST(rpv.value as float)), c.administrative_name as currency")
            ->from('report_position_value', 'rpv')
            ->innerJoin('rpv', 'report_position', 'rp', 'rpv.report_position_id = rp.id')
            ->innerJoin('rp', 'report', 'r', 'r.id = rp.report_id')
            ->innerJoin('r', 'facility_layout', 'fl', 'r.facility_layout_id = fl.id')
            ->innerJoin('fl', 'currency', 'c', 'c.id = fl.currency_id')
            ->innerJoin('rp', 'accounting_position', 'ap', 'ap.id = rp.accounting_position_id	')
            ->innerJoin('ap', 'accounting_category', 'ac', 'ac.id = ap.accounting_category_id	')
            ->groupBy('r.statement_date, c.administrative_name')
            ->where('r.id IN (:ids)')
            ->andWhere('r.deleted_at IS NULL')
            ->andWhere('ac.key = :reportType')
            ->setParameter('reportType', $reportType)
            ->setParameter('ids',  $ids, Connection::PARAM_INT_ARRAY)
            ->execute()
            ->fetch();
    }

    /**
     * @param Report $report
     * @param string $category
     * @param bool $showOnlyDeletedPositions
     * @return mixed
     */
    public function findReportPositionValuesByReportAndCategory(Report $report, string $category, $showOnlyDeletedPositions = false): array
    {
        $qb = $this->createQueryBuilder('reportPositionValue')
            ->innerJoin('reportPositionValue.reportPosition', 'reportPosition')
            ->innerJoin('reportPosition.report', 'report')
            ->innerJoin('reportPosition.accountingPosition', 'accountingPosition')
            ->innerJoin('accountingPosition.accountingCategory', 'accountingCategory')
            ->where('reportPosition.report = :report')
            ->andWhere('accountingCategory.key = :category')
            ->andWhere('report.deletedAt IS NULL')
            ->setParameter('category', $category)
            ->setParameter('report', $report);

        if ($showOnlyDeletedPositions) {
            $qb->andWhere('reportPosition.deletedAt IS NOT NULL')->andWhere('reportPositionValue.deletedAt IS NOT NULL');
        } else {
            $qb->andWhere('reportPosition.deletedAt IS NULL')->andWhere('reportPositionValue.deletedAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }
}
