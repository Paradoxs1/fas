<?php

namespace App\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Repository\FlexParamRepository;
use App\Service\FlexParamService;
use App\Service\ReportPositionService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExpenseHandler implements CategoryReportPositionHandlerInterface
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
     * @var ReportPositionService
     */
    private $reportPositionService;

    /**
     * ExpenseHandler constructor.
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
        $paymentMethodData = $this->reportService->getExpenses($report->getFacilityLayout(), $report);

        if (isset($paymentMethodData['data']) && !isset($requestData['expenses'])) {
            return true;
        }

        if (!isset($paymentMethodData['data']) && isset($requestData['expenses'])) {
            return true;
        }

        if (isset($paymentMethodData['data']) && isset($requestData['expenses'])) {
            if (count($paymentMethodData['data']) != count($requestData['expenses'])) {
                return true;
            }

            foreach ($paymentMethodData['data'] as $reportPosition => $data) {
                if (isset($requestData['expenses'][$reportPosition]['name']) && isset($requestData['expenses'][$reportPosition]['amount'])) {
                    if (
                        $data['name'] != $requestData['expenses'][$reportPosition]['name'] ||
                        $data['amount'] != $requestData['expenses'][$reportPosition]['amount']
                    ) {
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
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_EXPENSES_KEY);

            if ($values) {
                $account = $this->tokenStorage->getToken()->getUser();

                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPositionId = $value->getReportPosition()->getId();
                    $key = $value->getParameter()->getKey();

                    if (isset($requestData['expenses'])) {
                        if (isset($requestData['expenses'][$reportPositionId][$key])) {
                            if ($requestData['expenses'][$reportPositionId][$key] == $value->getValue()) {
                                unset($requestData['expenses'][$reportPositionId][$key]);
                                if (count($requestData['expenses'][$reportPositionId]) == 0) {
                                    unset($requestData['expenses'][$reportPositionId]);
                                }
                            }  else {
                                // value was changed
                                $value->setValue($requestData['expenses'][$reportPositionId][$key]);
                                $value->setModifiedBy($account);
                                $value->getReportPosition()->setModifiedBy($account);
                                if ($value->getParentReportPositionValue() !== null) {
                                    $value->setParentReportPositionValue($value->getParentReportPositionValue());
                                } else {
                                    $value->setParentReportPositionValue($value);
                                }

                                unset($requestData['expenses'][$reportPositionId][$key]);
                                if (count($requestData['expenses'][$reportPositionId]) == 0) {
                                    unset($requestData['expenses'][$reportPositionId]);
                                }
                            }
                        } else {
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
                        // all expense were removed
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

            if (isset($requestData['expenses']) && count($requestData['expenses']) > 0) {
                $this->addPositions($requestData['expenses'], $newReport, $this->tokenStorage->getToken()->getUser());
            }
        }
    }

    /**
     * @param array $expensesData
     * @param Report $report
     * @param Account $account
     */
    public function addPositions(array $expensesData, Report $report, Account $account)
    {
        if (count($expensesData) > 0) {
            /** @var FlexParamRepository $flexparamRepository */
            $flexparamRepository = $this->em->getRepository(FlexParam::class);

            $accountingCategory = $this->em->getRepository(AccountingCategory::class)->findOneByKey(FlexParamService::ACCOUNTING_CATEGORY_EXPENSES_KEY);

            /** @var AccountingPosition $accountingPosition */
            $accountingPosition = $this->reportPositionService->findAccountingPositionByCategoryAndLayout($accountingCategory, $report->getFacilityLayout());

            $expensesNameFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition, 'name', 'frontoffice');
            $expensesAmountFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition, 'amount', 'frontoffice');

            foreach ($expensesData as $expense) {
                if (isset($expense['name']) && isset($expense['amount'])) {
                    $reportPosition = new ReportPosition();
                    $reportPosition->setAccountingPosition($accountingPosition);
                    $reportPosition->setModifiedBy($account);

                    $reportPositionValueName = new ReportPositionValue();
                    $reportPositionValueName->setValue($expense['name']);
                    $reportPositionValueName->setParameter($expensesNameFlexParam);
                    $reportPositionValueName->setModifiedBy($account);
                    $reportPositionValueName->setSequence(1);

                    $reportPositionValueAmount = new ReportPositionValue();
                    $reportPositionValueAmount->setValue($expense['amount']);
                    $reportPositionValueAmount->setParameter($expensesAmountFlexParam);
                    $reportPositionValueAmount->setModifiedBy($account);
                    $reportPositionValueAmount->setSequence(2);

                    $reportPosition->addReportPositionValue($reportPositionValueName);
                    $reportPosition->addReportPositionValue($reportPositionValueAmount);

                    $report->addReportPosition($reportPosition);
                }
            }
        }
    }
}
