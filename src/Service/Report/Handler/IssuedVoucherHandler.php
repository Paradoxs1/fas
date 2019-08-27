<?php

namespace App\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\AccountingCategory;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Service\FlexParamService;
use App\Service\ReportPositionService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class IssuedVoucherHandler implements CategoryReportPositionHandlerInterface
{
    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ReportPositionService
     */
    private $reportPositionService;

    /**
     * IssuedVoucherHandler constructor.
     * @param ReportService $reportService
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param ReportPositionService $reportPositionService
     */
    public function __construct(ReportService $reportService, EntityManagerInterface $em, TokenStorageInterface $tokenStorage, ReportPositionService $reportPositionService)
    {
        $this->reportService = $reportService;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->reportPositionService = $reportPositionService;
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @return bool
     */
    public function checkChanges(Report $report, array $requestData): bool
    {
        $paymentMethodData = $this->reportService->getIssuedVouchers($report->getFacilityLayout(), $report);

        if (isset($paymentMethodData['data']) && !isset($requestData['issued-vouchers'])) {
            return true;
        }

        if (!isset($paymentMethodData['data']) && isset($requestData['issued-vouchers'])) {
            return true;
        }

        if (isset($paymentMethodData['data']) && isset($requestData['issued-vouchers'])) {
            if (count($paymentMethodData['data']) != count($requestData['issued-vouchers'])) {
                return true;
            }

            foreach ($paymentMethodData['data'] as $reportPosition => $data) {
                if (isset($requestData['issued-vouchers'][$reportPosition]['number'])) {
                    if ($data['number'] != $requestData['issued-vouchers'][$reportPosition]['number'] || $data['amount'] != $requestData['issued-vouchers'][$reportPosition]['amount']) {
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
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY);

            if ($values) {
                $account = $this->tokenStorage->getToken()->getUser();
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPositionId = $value->getReportPosition()->getId();
                    $key = $value->getParameter()->getKey();

                    if (isset($requestData['issued-vouchers'])) {
                        if (isset($requestData['issued-vouchers'][$reportPositionId][$key])) {
                            if ($requestData['issued-vouchers'][$reportPositionId][$key] == $value->getValue()) {
                                unset($requestData['issued-vouchers'][$reportPositionId][$key]);
                                if (count($requestData['issued-vouchers'][$reportPositionId]) == 0) {
                                    unset($requestData['issued-vouchers'][$reportPositionId]);
                                }
                            } else {
                                // value was changed
                                $value->setValue($requestData['issued-vouchers'][$reportPositionId][$key]);
                                $value->setModifiedBy($account);
                                $value->getReportPosition()->setModifiedBy($account);
                                if ($value->getParentReportPositionValue() !== null) {
                                    $value->setParentReportPositionValue($value->getParentReportPositionValue());
                                } else {
                                    $value->setParentReportPositionValue($value);
                                }

                                unset($requestData['issued-vouchers'][$reportPositionId][$key]);
                                if (count($requestData['issued-vouchers'][$reportPositionId]) == 0) {
                                    unset($requestData['issued-vouchers'][$reportPositionId]);
                                }
                            }
                        } else {
                            // value was removed
                            $value->setModifiedBy($account);
                            $value->setDeletedAt(new \DateTimeImmutable());
                            $value->getReportPosition()->setModifiedBy($account);
                            $value->getReportPosition()->setDeletedAt(new \DateTimeImmutable());
                            if ($value->getParentReportPositionValue() !== null) {
                                $value->setParentReportPositionValue($value->getParentReportPositionValue());
                            } else {
                                $value->setParentReportPositionValue($value);
                            }
                        }
                    } else {
                        // all vouchers were removed
                        $value->setModifiedBy($account);
                        $value->setDeletedAt(new \DateTimeImmutable());
                        $value->getReportPosition()->setModifiedBy($account);
                        $value->getReportPosition()->setDeletedAt(new \DateTimeImmutable());
                        if ($value->getParentReportPositionValue() !== null) {
                            $value->setParentReportPositionValue($value->getParentReportPositionValue());
                        } else {
                            $value->setParentReportPositionValue($value);
                        }
                    }
                }
            }

            if (isset($requestData['issued-vouchers']) && count($requestData['issued-vouchers']) > 0) {
                $this->addPositions($requestData['issued-vouchers'], $newReport, $this->tokenStorage->getToken()->getUser());
            }
        }
    }

    /**
     * @param array $vouchersData
     * @param Report $report
     * @param Account $account
     */
    public function addPositions(array $vouchersData, Report $report, Account $account)
    {

        $acceptedVoucherCategory = $this->em->getRepository(AccountingCategory::class)->findOneByKey(FlexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY);
        $accountingPosition = $this->reportPositionService->findAccountingPositionByCategoryAndLayout($acceptedVoucherCategory, $report->getFacilityLayout());

        /** @var FlexParamRepository $flexparamRepository */
        $flexparamRepository = $this->em->getRepository(FlexParam::class);
        $acceptedVoucherNumberFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition, 'number', 'frontoffice');
        $acceptedVoucherAmountFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition, 'amount', 'frontoffice');

        foreach ($vouchersData as $acceptedvoucher) {
            $reportPosition = new ReportPosition();
            $reportPosition->setAccountingPosition($accountingPosition);
            $reportPosition->setModifiedBy($account);

            $reportPositionValueNumber = new ReportPositionValue();
            $reportPositionValueNumber->setValue($acceptedvoucher['number']);
            $reportPositionValueNumber->setParameter($acceptedVoucherNumberFlexParam);
            $reportPositionValueNumber->setModifiedBy($account);
            $reportPositionValueNumber->setSequence(1);

            $reportPositionValueAmount = new ReportPositionValue();
            $reportPositionValueAmount->setValue($acceptedvoucher['amount']);
            $reportPositionValueAmount->setParameter($acceptedVoucherAmountFlexParam);
            $reportPositionValueAmount->setModifiedBy($account);
            $reportPositionValueAmount->setSequence(2);

            $reportPosition->addReportPositionValue($reportPositionValueNumber);
            $reportPosition->addReportPositionValue($reportPositionValueAmount);
            $report->addReportPosition($reportPosition);
        }
    }
}
