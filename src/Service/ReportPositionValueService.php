<?php

namespace App\Service;

use App\Entity\AccountingPosition;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionGroup;
use App\Entity\ReportPositionValue;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class ReportPositionValueService
 * @package App\Service
 */
class ReportPositionValueService
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


    private $reportPositionService;

    /**
     * AccountingInterfaceService constructor.
     * @param ObjectManager $manager
     * @param AccountingPositionService $accountingPositionService
     * @param ParameterBagInterface $params
     * @param ReportPositionService $reportPositionService
     */
    public function __construct(
        ObjectManager $manager,
        AccountingPositionService $accountingPositionService,
        ParameterBagInterface $params,
        ReportPositionService $reportPositionService
    ) {
        $this->manager                   = $manager;
        $this->accountingPositionService = $accountingPositionService;
        $this->params                    = $params;
        $this->reportPositionService     = $reportPositionService;
    }

    /**
     * @param ReportPosition $reportPosition
     * @param FlexParam $flexParam
     * @param $value
     * @param int $sequence
     * @param ReportPositionGroup|null $reportPositionGroup
     * @return ReportPositionValue
     */
    public function addReportPositionValue(
        ReportPosition $reportPosition,
        FlexParam $flexParam,
        $value,
        $sequence = 0,
        ?ReportPositionGroup $reportPositionGroup = null
    ): ReportPositionValue {
        $reportPositionValue = new ReportPositionValue();
        $reportPositionValue->setReportPosition($reportPosition);
        $reportPositionValue->setParameter($flexParam);
        $reportPositionValue->setValue($value);
        $reportPositionValue->setSequence($sequence);

        if ($reportPositionGroup) {
            $reportPositionValue->setReportPositionGroup($reportPositionGroup);
        }

        $this->manager->persist($reportPositionValue);
        $this->manager->flush();

        return $reportPositionValue;
    }

    /**
     * @param AccountingPosition $accountingPosition
     * @param Report $report
     * @return ReportPositionValue|null
     */
    public function getReportPositionValueByAccountingPositionAndReport(AccountingPosition $accountingPosition, Report $report): ?ReportPositionValue
    {
        $reportPosition = $this->reportPositionService->findReportPositionByAccountingPositionAndReport(
            $accountingPosition,
            $report
        );

        if (!$reportPosition) {
            return null;
        }

        return $this->manager->getRepository(ReportPositionValue::class)->findOneBy(['reportPosition' => $reportPosition]);
    }

    /**
     * @param FlexParam $flexParam
     * @return ReportPositionValue|null
     */
    public function getReportPositionValueByFlexParam(FlexParam $flexParam): ?array
    {
        return $this->manager->getRepository(ReportPositionValue::class)
            ->findBy(['parameter' => $flexParam->getId(), 'deletedAt' => null], ['sequence' => 'ASC']);
    }
}
