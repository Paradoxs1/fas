<?php

namespace App\Repository;

use App\Entity\AccountingCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AccountingCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountingCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountingCategory[]    findAll()
 * @method AccountingCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountingCategoryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AccountingCategory::class);
    }

    /**
     * @param array $categories
     * @return mixed
     */
    public function findByCategories(
        array $categories = []
    ) {
        return
            $this
                ->createQueryBuilder('accountingCategory')
                ->where('accountingCategory.key IN (:accountingCategoryIds)')
                ->setParameter('accountingCategoryIds', $categories)
                ->getQuery()
                ->getResult()
            ;
    }
}
