<?php

namespace App\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Entity\ReportPositionValue;
use App\Service\Report\CategoryReportPositionHandlerComposite;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TotalSalesHandler implements CategoryReportPositionHandlerInterface
{
    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Account $loggedUser
     */
    private $loggedUser;

    /**
     * TotalSalesHandler constructor.
     * @param ReportService $reportService
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $em
     */
    public function __construct(ReportService $reportService, TokenStorageInterface $tokenStorage, EntityManagerInterface $em)
    {
        $this->reportService = $reportService;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkChanges(Report $report, array $requestData): bool
    {
        if (isset($requestData['total-sales']) && $requestData['total-sales']) {
            $totalSales = CategoryReportPositionHandlerComposite::formatAmount($requestData['total-sales']);
            if ($totalSales != $this->reportService->getTotalSales($report->getFacilityLayout(), $report)->getValue() ) {
                return true;
            }
        }

        if (isset($requestData['sales']) && $requestData['sales']) {
            $array = $this->reportService->getSales($report->getFacilityLayout(), $report);
            foreach ($array as $key => $item) {
                if (CategoryReportPositionHandlerComposite::formatAmount($requestData['sales'][$key]) != $item['value']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Report $newReport
     * @param Report $oldReport
     * @param array $requestData
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function applyChanges(Report $newReport, Report $oldReport, array $requestData): void
    {
        if ($this->checkChanges($oldReport, $requestData)) {
            $this->loggedUser = $this->tokenStorage->getToken()->getUser();

            if (isset($requestData['total-sales'])) {
                /** @var ReportPositionValue $oldReportPositionValue */
                $reportPositionValue =  $this->reportService->getTotalSales($oldReport->getFacilityLayout(), $oldReport);
                $this->modifiedReportPosition($reportPositionValue, CategoryReportPositionHandlerComposite::formatAmount($requestData['total-sales']));
            }

            if (isset($requestData['sales'])) {
                $reportPositionValues = $this->reportService->getSales($oldReport->getFacilityLayout(), $oldReport, true);
                /** @var ReportPositionValue $oldReportPositionValue */
                foreach ($reportPositionValues as $key => $reportPositionValue) {
                    $this->modifiedReportPosition($reportPositionValue, CategoryReportPositionHandlerComposite::formatAmount($requestData['sales'][$key]));
                }
            }
        }
    }

    /**
     * @param ReportPositionValue $reportPositionValue
     * @param string $value
     * @return void
     */
    private function modifiedReportPosition(ReportPositionValue $reportPositionValue, string $value): void
    {
        $reportPosition = $reportPositionValue->getReportPosition();
        $reportPosition->setModifiedBy($this->loggedUser);
        $reportPositionValue->setModifiedBy($this->loggedUser);
        $reportPositionValue->setValue($value);
        if ($reportPositionValue->getParentReportPositionValue() !== null) {
            $reportPositionValue->setParentReportPositionValue($reportPositionValue->getParentReportPositionValue());
        } else {
            $reportPositionValue->setParentReportPositionValue($reportPositionValue);
        }
    }
}
