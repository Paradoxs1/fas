<?php

namespace App\Service\Report\Handler;

use App\Entity\Report;
use App\Entity\ReportPositionValue;
use App\Service\FlexParamService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CashIncomeHandler implements CategoryReportPositionHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * CashIncomeHandler constructor.
     * @param ReportService $reportService
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ReportService $reportService, EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->reportService = $reportService;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Report $report
     * @param array $data
     * @return bool
     */
    public function checkChanges(Report $report, array $data): bool
    {
        $cashIncome = $this->reportService->getCash($report->getFacilityLayout(), $report);

        if ($cashIncome && $cashIncome['value'] != $data['cash-income']) {
            return true;
        }

        return false;
    }

    /**
     * @param Report $newReport
     * @param Report $oldReport
     * @param array $requestData
     * @return void
     */
    public function applyChanges(Report $newReport, Report $oldReport, array $requestData): void
    {
        $account = $this->tokenStorage->getToken()->getUser();

        if ($this->checkChanges($oldReport, $requestData)) {
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_CASH_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $value->setModifiedBy($account);
                    $value->setValue($requestData['cash-income']);
                    $value->getReportPosition()->setModifiedBy($account);
                    $value->getReportPosition()->setModifiedAt();
                    if ($value->getParentReportPositionValue() !== null) {
                        $value->setParentReportPositionValue($value->getParentReportPositionValue());
                    } else {
                        $value->setParentReportPositionValue($value);
                    }
                }
            }
        }
    }
}
