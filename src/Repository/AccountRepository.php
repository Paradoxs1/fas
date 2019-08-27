<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method Account|null find($id, $lockMode = null, $lockVersion = null)
 * @method Account|null findOneBy(array $criteria, array $orderBy = null)
 * @method Account[]    findAll()
 * @method Account[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRepository extends ServiceEntityRepository implements UserLoaderInterface, LoggerRepositoryInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @param null|int $id
     * @return QueryBuilder|null
     */
    public function findAllTenantManagersQueryBuilder(?int $id = null): ?QueryBuilder
    {
        $queryBuilder = $this
           ->createQueryBuilder('account')
           ->join('account.accountFacilityRoles', 'afr')
           ->join('afr.role', 'role')
           ->where('role.internalName = :internalName')
           ->andWhere('account.deletedAt IS NULL');

        if (isset($id)) {
            $queryBuilder
                ->andWhere('account.tenant = :tenant')
                ->setParameter('tenant', $id);
        }

        return $queryBuilder
            ->orderBy('account.id', 'ASC')
            ->setParameter('internalName', 'ROLE_TENANT_MANAGER')
       ;
    }

    /**
     * @param null|int $id
     * @return array
     */
    public function findAllTenantManagersForTenantEdit(?int $id = null): array
    {
        return $this
            ->findAllTenantManagersQueryBuilder($id)
            ->getQuery()->getResult()
        ;
    }

    /**
     * @return mixed
     */
    public function findAllTenantManagersForTenantToAdd()
    {
        return $this
            ->findAllTenantManagersQueryBuilder()
            ->andWhere('account.tenant IS NULL')
            ->getQuery()->getResult()
        ;
    }

    /**
     * @param $value
     * @return Account|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findUserByLogin($value): ?Account
    {
        return $this
            ->createQueryBuilder('account')
            ->where('account.login = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param string $username
     * @return mixed|null|\Symfony\Component\Security\Core\User\UserInterface
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($username)
    {
        return $this
            ->createQueryBuilder('account')
            ->where('account.login = :username')
            ->andWhere('account.deletedAt IS NULL')
            ->setParameter('username', strtolower($username))
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param Facility $facility
     * @param array $roles
     * @return QueryBuilder
     */
    public function getFacilityUsersByRoles(Facility $facility, array $roles = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('account')
            ->innerJoin('account.person', 'person')
            ->innerJoin('account.accountFacilityRoles', 'afr')
            ->where('afr.facility = :facility')
            ->andWhere('account.deletedAt IS NULL')
            ->setParameter('facility', $facility);

        if ($roles) {
            $queryBuilder
                ->innerJoin('afr.role', 'role')
                ->andWhere('role.internalName IN (:roles)')
                ->setParameter('roles', $roles, Connection::PARAM_INT_ARRAY);
        }
        return $queryBuilder;
    }



    /**
     * @param Tenant $tenant
     * @param array $roles
     * @return QueryBuilder
     */
    public function getTenantUsersByRoles(Tenant $tenant, array $roles = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('account')
            ->innerJoin('account.person', 'person')
            ->where('account.tenant = :tenant')
            ->andWhere('account.deletedAt is NULL')
            ->setParameter('tenant', $tenant);

        if ($roles) {
            $queryBuilder
                ->innerJoin('account.accountFacilityRoles', 'facility_role')
                ->innerJoin('facility_role.role', 'role')
                ->andWhere('role.internalName IN (:roles)')
                ->setParameter('roles', $roles, Connection::PARAM_INT_ARRAY);
        }
        return $queryBuilder;
    }

    public function getAccountByFacilityAndRoles(int $accountId, Facility $facility, array $roles = [])
    {
        $queryBuilder = $this->getFacilityUsersByRoles($facility, $roles)
            ->andWhere('account.id = :accountId')
            ->setParameter('accountId', $accountId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }


    public function getEntityArrayResult(string $name, $value): array
    {
        $conn = $this->_em->getConnection();
        return $conn->createQueryBuilder()
            ->select('*')
            ->from('account', 'a')
            ->where('a.' . $name . ' = :value')
            ->setParameter('value', $value)
            ->execute()
            ->fetchAll();
    }

    public function getMaxId()
    {
        return $this
            ->createQueryBuilder('account')
            ->orderBy('account.id', 'DESC' )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
