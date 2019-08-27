<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\FacilityLayout;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NonUniqueResultException;

use phpDocumentor\Reflection\Types\Integer;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class ReportPositionService
 * @package App\Service
 */
class ReportPositionService
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @var ObjectManager
     */
    private $accountingPositionService;

    /**
     * AccountingInterfaceService constructor.
     * @param ObjectManager $manager
     */
    public function __construct(
        ObjectManager $manager,
        AccountingPositionService $accountingPositionService,
        ParameterBagInterface $params
    )
    {
        $this->manager                   = $manager;
        $this->accountingPositionService = $accountingPositionService;
        $this->params                    = $params;
    }

    /**
     * @param Report $report
     * @param AccountingPosition $accountingPosition
     * @param Account $user
     * @return ReportPosition
     */
    public function addReportPosition(
        Report $report,
        AccountingPosition $accountingPosition,
        Account $user
    ): ReportPosition {
        $reportPosition = new ReportPosition();

        $reportPosition->setReport($report);
        $reportPosition->setAccountingPosition($accountingPosition);
        $reportPosition->setCreatedBy($user);

        $this->manager->persist($reportPosition);
        $this->manager->flush();

        return $reportPosition;
    }

    /**
     * @param AccountingCategory $accountingCategory
     * @param FacilityLayout $facilityLayout
     * @return AccountingPosition|null
     * @throws NonUniqueResultException
     */
    public function findAccountingPositionByCategoryAndLayout(
        AccountingCategory $accountingCategory,
        FacilityLayout $facilityLayout
    ): ?AccountingPosition {

        $accountingPosition = $this->manager->getRepository(AccountingPosition::class)
            ->getAccountingPositionByCategoryAndLayout($accountingCategory, $facilityLayout);

        return $accountingPosition ?: null;
    }

    /**
     * @param AccountingPosition $accountingPosition
     * @param Report $report
     * @return ReportPosition|null
     */
    public function findReportPositionByAccountingPositionAndReport(
        AccountingPosition $accountingPosition,
        Report $report
    ): ?ReportPosition
    {
        return $this->manager->getRepository(ReportPosition::class)
            ->findOneBy([
                'accountingPosition' => $accountingPosition->getId(),
                'report' => $report->getId(),
                'deletedAt' => null
            ]
        );
    }
}
