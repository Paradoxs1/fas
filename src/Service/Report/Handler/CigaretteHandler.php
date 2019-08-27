<?php

namespace App\Service\Report\Handler;

use App\Entity\Report;
use App\Entity\ReportPositionValue;
use App\Service\FlexParamService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CigaretteHandler implements CategoryReportPositionHandlerInterface
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
     * CigaretteHandler constructor.
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
        $paymentMethodData = $this->reportService->getCigarettes($report->getFacilityLayout(), $report);

        if ($paymentMethodData) {
            if (isset($requestData['cigarettes'])) {
                if (!isset($paymentMethodData['value'])) {
                    return true;
                }

                if ($requestData['cigarettes'] != $paymentMethodData['value']) {
                    return true;
                }
            } else {
                return true;
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
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_CIGARETTES_KEY);

            if ($values) {
                $account = $this->tokenStorage->getToken()->getUser();

                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    if (isset($requestData['cigarettes'])) {
                        if ($requestData['cigarettes'] != $value->getValue()) {
                            $value->setModifiedBy($account);
                            $value->setValue(str_replace("'",'', $requestData['cigarettes']));
                            $value->getReportPosition()->setModifiedBy($account);
                            $value->getReportPosition()->setModifiedAt(new \DateTimeImmutable());
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
