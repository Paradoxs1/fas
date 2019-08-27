<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Facility|null find($id, $lockMode = null, $lockVersion = null)
 * @method Facility|null findOneBy(array $criteria, array $orderBy = null)
 * @method Facility[]    findAll()
 * @method Facility[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FacilityRepository extends ServiceEntityRepository implements LoggerRepositoryInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Facility::class);
    }

    /**
     * @param Tenant $tenant
     * @return QueryBuilder|null
     */
    public function findAllTenantFacilitiesQueryBuilder(Tenant $tenant): ?QueryBuilder
    {
        return $this->createQueryBuilder('facility')
            ->andWhere('facility.tenant = :tenantId')
            ->andWhere('facility.deletedAt IS NULL')
            ->setParameter('tenantId', $tenant->getId())
            ->orderBy('facility.id', 'ASC')
            ->setMaxResults(10)
        ;
    }

    /**
     * @param Tenant $tenant
     * @param array $roles
     * @return array
     */
    public function getTenantFacilitiesByAccountRoles(Account $account, array $roles = []): array
    {
        $queryBuilder = $this->createQueryBuilder('facility')
            ->where('facility.tenant = :tenant')
            ->andWhere('facility.deletedAt IS NULL')
            ->setParameter('tenant', $account->getTenant());

        if ($roles) {
            $queryBuilder
                ->innerJoin('facility.accountFacilityRole', 'facility_role')
                ->innerJoin('facility_role.role', 'role')
                ->andWhere('facility_role.account = :account')
                ->andWhere('role.internalName IN (:roles)')
                ->setParameter('account', $account)
                ->setParameter('roles', $roles, Connection::PARAM_INT_ARRAY);
        }
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $id
     * @param Account $account
     * @param array $roles
     * @return mixed
     */
    public function getFacilityByIdAndAccountRoles(int $id, Account $account, array $roles = [])
    {
        $queryBuilder = $this->createQueryBuilder('facility')
            ->where('facility.tenant = :tenant')
            ->andWhere('facility.deletedAt IS NULL')
            ->andWhere('facility.id = :id')
            ->setParameter('id', $id)
            ->setParameter('tenant', $account->getTenant());

        if ($roles) {
            $queryBuilder
                ->innerJoin('facility.accountFacilityRole', 'facility_role')
                ->innerJoin('facility_role.role', 'role')
                ->andWhere('facility_role.account = :account')
                ->andWhere('role.internalName IN (:roles)')
                ->setParameter('account', $account)
                ->setParameter('roles', $roles, Connection::PARAM_INT_ARRAY);
        }
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $startData
     * @param string $endDate
     * @param int $id
     * @return mixed[]
     */
    public function getSumTipsCurrentFacility(string $startData, string $endDate, int $id)
    {
        $conn = $this->_em->getConnection();
        return $conn->createQueryBuilder()
            ->select('fp.value as value, rpv.value as sum, c.iso_code as iso_code, r.id as id')
            ->from('report_position_value', 'rpv')
            ->join('rpv', 'report_position', 'rp', 'rpv.report_position_id = rp.id')
            ->join('rp', 'report', 'r', 'rp.report_id = r.id')
            ->join('rp', 'accounting_position', 'ap', 'rp.accounting_position_id = ap.id')
            ->join('ap', 'accounting_category', 'ac', 'ap.accounting_category_id = ac.id')
            ->join('ap', 'flex_param','fp', 'fp.accounting_position_id = ap.id')
            ->join('ap', 'facility_layout', 'fl', 'ap.facility_layout_id = fl.id')
            ->join('ap', 'currency', 'c', 'ap.currency_id = c.id')
            ->join('fl', 'facility', 'f', 'fl.facility_id = :id')
            ->where('ac.key = :tip')
            ->andWhere('fp.key = :name')
            ->andWhere('r.statement_date BETWEEN :startData AND :endDate')
            ->andWhere('r.deleted_at is null')
            ->groupBy('fp.value, c.iso_code, rpv.value, r.id')
            ->setParameter('id', $id)
            ->setParameter('tip', 'tip')
            ->setParameter('name', 'name')
            ->setParameter('startData', $startData)
            ->setParameter('endDate', $endDate)
            ->execute()
            ->fetchAll();
    }

    public function getEntityArrayResult(string $name, $value): array
    {
        $conn = $this->_em->getConnection();
        return $conn->createQueryBuilder()
            ->select('*')
            ->from('facility', 'f')
            ->join('f', 'address', 'a', 'f.address_id = a.id')
            ->where('f.' . $name . ' = :value')
            ->setParameter('value', $value)
            ->execute()
            ->fetchAll();
    }
}
