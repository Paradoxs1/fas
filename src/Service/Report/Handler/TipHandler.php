<?php

namespace App\Service\Report\Handler;

use App\Entity\Report;
use App\Entity\ReportPositionValue;
use App\Service\FlexParamService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TipHandler implements CategoryReportPositionHandlerInterface
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
     * TipHandler constructor.
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
     * @param array $requestData
     * @return bool
     */
    public function checkChanges(Report $report, array $requestData): bool
    {
        $tips = $this->reportService->getTips($report->getFacilityLayout(), $report);

        if ($tips && !isset($requestData['tips'])) {
            return true;
        }

        if (!$tips && isset($requestData['tips'])) {
            return true;
        }

        if ($tips && $requestData['tips']) {
            foreach ($tips as $reportPositionId => $tip) {
                if (isset($requestData['tips'][$reportPositionId])) {
                    if ($requestData['tips'][$reportPositionId] != $tip['value']) {
                        return true;
                    }
                } else {
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
     */
    public function applyChanges(Report $newReport, Report $oldReport, array $requestData): void
    {
        if ($this->checkChanges($oldReport, $requestData)) {
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_TIP_KEY);

            if ($values) {
                $account = $this->tokenStorage->getToken()->getUser();

                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $accountingPositionId = $value->getReportPosition()->getAccountingPosition()->getId();

                    if (isset($requestData['tips'])) {
                        if ($requestData['tips'][$accountingPositionId] != $value->getValue()) {
                            $value->setModifiedBy($account);
                            $value->setValue($requestData['tips'][$accountingPositionId]);
                            $value->getReportPosition()->setModifiedBy($account);
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
    }
}
