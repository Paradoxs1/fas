<?php

namespace App\Repository;

use App\Entity\AccountFacilityRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AccountFacilityRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountFacilityRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountFacilityRole[]    findAll()
 * @method AccountFacilityRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountFacilityRoleRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AccountFacilityRole::class);
    }

    /**
     * @param $value
     * @return array|null
     */
    public function findByFacility($value): ?array
    {
        return $this->createQueryBuilder('account_facility_role')
            ->andWhere('account_facility_role.facility = :facility')
            ->setParameter('facility', $value)
            ->getQuery()
            ->getResult()
        ;
    }
}
