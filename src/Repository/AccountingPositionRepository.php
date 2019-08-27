<?php

namespace App\Repository;

use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\FacilityLayout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AccountingPosition|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountingPosition|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountingPosition[]    findAll()
 * @method AccountingPosition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountingPositionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AccountingPosition::class);
    }

    /**
     * @param AccountingCategory $accountingCategory
     * @param FacilityLayout $facilityLayout
     * @return AccountingPosition|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAccountingPositionByCategoryAndLayout(
        AccountingCategory $accountingCategory,
        FacilityLayout $facilityLayout
    ): ?AccountingPosition {

        return $this
            ->createQueryBuilder('accountingPosition')
            ->where('accountingPosition.accountingCategory = :accountingCategory')
            ->andWhere('accountingPosition.facilityLayout = :facilityLayout')
            ->setParameter('accountingCategory', $accountingCategory)
            ->setParameter('facilityLayout', $facilityLayout)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
