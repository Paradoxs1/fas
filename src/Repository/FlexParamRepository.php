<?php

namespace App\Repository;

use App\Entity\AccountingPosition;
use App\Entity\FlexParam;
use App\Entity\FacilityLayout;
use App\Entity\AccountingCategory;
use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FlexParam|null find($id, $lockMode = null, $lockVersion = null)
 * @method FlexParam|null findOneBy(array $criteria, array $orderBy = null)
 * @method FlexParam[]    findAll()
 * @method FlexParam[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FlexParamRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FlexParam::class);
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param AccountingCategory $accountingCategory
     * @param string $view
     * @param Report|null $report
     * @return mixed
     */
    public function findByCategoryAndFacilityLayout(
        FacilityLayout $facilityLayout,
        AccountingCategory $accountingCategory,
        $view = 'backoffice',
        Report $report = null
    ) {
            $queryBuilder = $this
                ->createQueryBuilder('flexParam')
                ->join('flexParam.accountingPosition', 'flexParamAccountingPosition')
                ->where('flexParamAccountingPosition.accountingCategory = :accountingCategoryId')
                ->andWhere('flexParamAccountingPosition.facilityLayout = :facilityLayoutId');

        if ($report) {
            $queryBuilder->andWhere('flexParamAccountingPosition.reportId = :reportId');
        }

        if ('both' != $view) {
            $queryBuilder->andWhere('flexParam.view = :view');
        }

        $queryBuilder
            ->setParameter('accountingCategoryId', $accountingCategory->getId())
            ->setParameter('facilityLayoutId', $facilityLayout->getId());

        if ('both' != $view) {
            $queryBuilder->setParameter('view', $view);
        }

        if ($report) {
            $queryBuilder->setParameter('reportId', $report->getId());
        }

        $queryBuilder->orderBy('flexParamAccountingPosition.sequence', 'ASC');

        if (in_array($view, ['frontoffice', 'both'])) {
            $queryBuilder->addOrderBy('flexParam.sequence', 'ASC');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param array $categories
     * @return mixed
     */
    public function findByCategoriesAndFacilityLayout(
        FacilityLayout $facilityLayout,
        array $categories = []
    ) {
        return
            $this
                ->createQueryBuilder('flexParam')

                ->join('flexParam.accountingPosition', 'flexParamAccountingPosition')
                ->join('flexParamAccountingPosition.accountingCategory', 'accountingCategory')

                ->where('accountingCategory.key IN (:accountingCategoryIds)')

                ->andWhere('flexParamAccountingPosition.facilityLayout = :facilityLayoutId')
                ->andWhere('flexParam.view = :view')

                ->setParameter('accountingCategoryIds', $categories)
                ->setParameter('facilityLayoutId', $facilityLayout->getId())
                ->setParameter('view', 'backoffice')

                //Sorting of Flex params group
                ->orderBy('accountingCategory.sequence', 'ASC')

                //Sorting of Flex params group items
                ->addOrderBy('flexParamAccountingPosition.sequence', 'ASC')
                ->addOrderBy('flexParam.sequence', 'ASC')

                ->getQuery()
                ->getResult()
            ;
    }

    /**
     * @param AccountingPosition $accountingPosition
     * @param $key
     * @param string $view
     * @return FlexParam|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByAccountingPositionAndType(AccountingPosition $accountingPosition, $key, $view = ''): ?FlexParam
    {
        $queryBuilder = $this
            ->createQueryBuilder('flexParam')
            ->join('flexParam.accountingPosition', 'flexParamAccountingPosition')
            ->where('flexParamAccountingPosition = :flexParamAccountingPositionId')
            ->andWhere('flexParam.key = :key')
            ->setParameter('flexParamAccountingPositionId', $accountingPosition->getId())
            ->setParameter('key', $key)
        ;

        if ($view && in_array($view, ['frontoffice', 'backoffice'])) {
            $queryBuilder
                ->andWhere('flexParam.view = :view')
                ->setParameter('view', $view)
            ;
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
