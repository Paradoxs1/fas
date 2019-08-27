<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\Report;
use App\Service\FlexParamService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @method Report|null find($id, $lockMode = null, $lockVersion = null)
 * @method Report|null findOneBy(array $criteria, array $orderBy = null)
 * @method Report[]    findAll()
 * @method Report[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * @param Facility $facility
     * @param Account|null $user
     * @param $statementDate
     * @param int|null $type
     * @param null $approved
     * @return Report|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByFacilityUserStatementDate(Facility $facility, ?Account $user, $statementDate, int $type = null, $approved = null): ?Report
    {
        $qb = $this
            ->createQueryBuilder('report')
            ->leftJoin('report.facilityLayout', 'facilityLayout')
            ->leftJoin('facilityLayout.facility', 'facility')
            ->andWhere('facility.id = :facility')
            ->andWhere('report.statementDate = :statementDate')
            ->setParameter('facility', $facility->getId())
            ->setParameter('statementDate', $statementDate)
            ->andWhere('report.deletedAt IS NULL')
            ->setMaxResults(1);

        if ($user) {
            $qb->andWhere('report.createdBy = :createdBy')
                ->setParameter('createdBy', $user->getId());
        }

        if ($type) {
            $qb->andWhere('report.type = :type')->setParameter('type', $type);
        }

        if ($approved !== null) {
            $qb->andWhere('report.approved = :approved')->setParameter('approved', $approved);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $id
     * @param $statementDate
     * @param int|null $type
     * @param null $approved
     * @return Report|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByIdAndStatementDate(int $id, $statementDate, int $type = null, $approved = null): ?Report
    {
        $qb = $this
            ->createQueryBuilder('report')
            ->where('report.id = :id')
            ->andWhere('report.statementDate = :statementDate')
            ->setParameter('statementDate', $statementDate)
            ->setParameter('id', $id);

        if ($type) {
            $qb->andWhere('report.type = :type')->setParameter('type', $type);
        }

        if ($approved !== null) {
            $qb->andWhere('report.approved = :approved')->setParameter('approved', $approved);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Facility $facility
     * @param $statementDate
     * @param int|null $type
     * @param null $approved
     * @return Report|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByFacilityStatementDate(Facility $facility, $statementDate, int $type = null, $approved = null): ?Report
    {
        $qb = $this
            ->createQueryBuilder('report')
            ->leftJoin('report.facilityLayout', 'facilityLayout')
            ->leftJoin('facilityLayout.facility', 'facility')
            ->andWhere('facility.id = :facility')
            ->andWhere('report.statementDate = :statementDate')
            ->setParameter('facility', $facility->getId())
            ->setParameter('statementDate', $statementDate);

        if ($type) {
            $qb->andWhere('report.type = :type')->setParameter('type', $type);
        }

        if ($approved !== null) {
            $qb->andWhere('report.approved = :approved')->setParameter('approved', $approved);
        }

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    /**
     * @param Facility $facility
     * @return array|null
     */
    public function findAllFacilityReports(Facility $facility): ?Array
    {
        return $this
            ->createQueryBuilder('report')
            ->leftJoin('report.facilityLayout', 'facilityLayout')
            ->leftJoin('facilityLayout.facility', 'facility')
            ->where('facility.deletedAt IS NULL')
            ->andWhere('report.deletedAt IS NULL')
            ->andWhere('facility.id = :facility')
            ->setParameter('facility', $facility->getId())
            ->getQuery()
            ->getResult(Query::HYDRATE_OBJECT)
        ;
    }

    /**
     * @param Facility $facility
     * @param null|bool $approved
     * @return QueryBuilder
     */
    public function getReportsListQueryBuilder(Facility $facility, ?bool $approved = null): QueryBuilder
    {
        $conn = $this->_em->getConnection();
        $qb = $conn->createQueryBuilder()
            ->select("COUNT(report.id) as cnt, array_to_string(array_agg(concat(report.id, '-',report.type)), ',') as report_data, report.statement_date as date, f.name as facility_name")
            ->from('report', 'report')
            ->innerJoin('report', 'facility_layout', 'fl', 'report.facility_layout_id	= fl.id')
            ->innerJoin('fl', 'facility', 'f', 'fl.facility_id = f.id')
            ->groupBy('report.statement_date, f.name')
            ->where('f.deleted_at IS NULL')
            ->andWhere('report.deleted_at IS NULL')
            ->andWhere('f.id = :facility')
            ->setParameter('facility', $facility->getId())
            ->orderBy('report.statement_date', 'DESC');

        if ($approved) {
            $qb->having('sum(cast(report.approved as int)) = :approved')
                ->setParameter('approved', 0);
        }

        return $qb;
    }

    /**
     * @param Facility $facility
     * @param string $date
     * @param int|null $type
     * @param null $approved
     * @return mixed
     */
    public function getReportsByFacilityAndDate(Facility $facility, string $date, int $type = null, $approved = null)
    {
        $qb = $this
            ->createQueryBuilder('report')
            ->leftJoin('report.facilityLayout', 'facilityLayout')
            ->leftJoin('facilityLayout.facility', 'facility')
            ->where('facility = :facility')
            ->andWhere('facility.deletedAt IS NULL')
            ->andWhere('report.deletedAt IS NULL')
            ->andWhere('report.statementDate = :statementDate')
            ->setParameter('facility', $facility)
            ->setParameter('statementDate', $date);

        if ($type) {
            $qb->andWhere('report.type = :type')->setParameter('type', $type);
        }

        if ($approved !== null) {
            $qb->andWhere('report.approved = :approved')->setParameter('approved', $approved);
        }

        return $qb->getQuery()->getResult();
    }

    public function getReportsByFacilityBetweenStatementDates(Facility $facility): ?array
    {
        $conn = $this->_em->getConnection();
        return $conn->createQueryBuilder()
            ->select("SUM(ROUND(CAST(rpv.value as float))) as total, to_char(report.statement_date, 'DD.MM.YYYY') as date")
            ->from('report', 'report')
            ->innerJoin('report', 'facility_layout', 'fl', 'report.facility_layout_id	= fl.id')
            ->innerJoin('fl', 'facility', 'f', 'fl.facility_id = f.id')
            ->innerJoin('report', 'report_position', 'rp', 'rp.report_id = report.id')
            ->innerJoin('rp', 'accounting_position', 'ap', 'rp.accounting_position_id = ap.id')
            ->innerJoin('ap', 'accounting_category', 'ac', 'ap.accounting_category_id = ac.id')
            ->innerJoin('rp', 'report_position_value', 'rpv', 'rpv.report_position_id = rp.id')
            ->where('f.deleted_at IS NULL')
            ->andWhere('report.deleted_at IS NULL')
            ->andWhere('rpv.deleted_at IS NULL')
            ->andWhere('f.id = :facility')
            ->andWhere('report.approved = :approved')
            ->andWhere("report.statement_date BETWEEN TIMESTAMP 'yesterday' - INTERVAL '7 DAYS' AND TIMESTAMP 'yesterday'")
            ->andWhere("
                CASE 
                    WHEN (report.type = :typeBackofficer) THEN ac.key = :categoryBackofficer 
                    WHEN (report.type = :typeMigrate) THEN ac.key = :categoryMigrate 
                END
            ")
            ->setParameter('facility', $facility->getId())
            ->setParameter('typeBackofficer', Report::REPORT_TYPE_BACKOFFICER)
            ->setParameter('typeMigrate', Report::REPORT_TYPE_MIGRATION)
            ->setParameter('approved', Report::REPORT_APPROVED)
            ->setParameter('categoryBackofficer', FlexParamService::ACCOUNTING_CATEGORY_SALES_CATEGORY_KEY)
            ->setParameter('categoryMigrate', FlexParamService::ACCOUNTING_CATEGORY_TOTAL_SALES_KEY)
            ->groupBy("date")
            ->execute()
            ->fetchAll();
    }

    public function getLatestApprovedReport(Facility $facility)
    {
        $qb = $this->createQueryBuilder('report');
        return $qb
            ->leftJoin('report.facilityLayout', 'facilityLayout')
            ->leftJoin('facilityLayout.facility', 'facility')
            ->where('facility = :facility')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('report.type', '?1'),
                $qb->expr()->eq('report.type', '?2')
            ))
            ->andWhere('report.approved = :approved')
            ->andWhere('report.number IS NOT NULL')
            ->setParameters([1 => Report::REPORT_TYPE_MIGRATION, 2 => Report::REPORT_TYPE_BACKOFFICER])
            ->setParameter('facility', $facility)
            ->setParameter('approved', Report::REPORT_APPROVED)
            ->orderBy('report.number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }
}
