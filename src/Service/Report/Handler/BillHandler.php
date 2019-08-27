<?php

namespace App\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\AccountingPosition;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Repository\FlexParamRepository;
use App\Service\FlexParamService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BillHandler implements CategoryReportPositionHandlerInterface
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
     * BillHandler constructor.
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
        $paymentMethodData = $this->reportService->getBills($report->getFacilityLayout(), $report);

        if (isset($paymentMethodData['data']) && !isset($requestData['bills'])) {
            return true;
        }

        if (!isset($paymentMethodData['data']) && isset($requestData['bills'])) {
            return true;
        }

        if (isset($paymentMethodData['data']) && isset($requestData['bills'])) {

            if (count($paymentMethodData['data']) != count($requestData['bills'])) {
                return true;
            }

            foreach ($paymentMethodData['data'] as $reportPosition => $data) {
                if (isset($requestData['bills'][$reportPosition]['receiver']) && isset($requestData['bills'][$reportPosition]['name']) && isset($requestData['bills'][$reportPosition]['amount']) && isset($requestData['bills'][$reportPosition]['tip'])) {
                    if (
                        $data['receiver'] != $requestData['bills'][$reportPosition]['receiver'] ||
                        $data['name'] != $requestData['bills'][$reportPosition]['name'] ||
                        $data['amount'] != $requestData['bills'][$reportPosition]['amount'] ||
                        $data['tip'] != $requestData['bills'][$reportPosition]['tip']
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
     * @return void
     * @throws \Exception
     */
    public function applyChanges(Report $newReport, Report $oldReport, array $requestData): void
    {
        if ($this->checkChanges($oldReport, $requestData)) {
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_BILL_KEY);

            if ($values) {
                $account = $this->tokenStorage->getToken()->getUser();

                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPositionId = $value->getReportPosition()->getId();
                    $key = $value->getParameter()->getKey();

                    if (isset($requestData['bills'])) {
                        if (isset($requestData['bills'][$reportPositionId][$key])) {
                            if ($requestData['bills'][$reportPositionId][$key] == $value->getValue()) {
                                unset($requestData['bills'][$reportPositionId][$key]);
                                if (count($requestData['bills'][$reportPositionId]) == 0) {
                                    unset($requestData['bills'][$reportPositionId]);
                                }
                            } else {
                                // value was changed
                                $value->setValue($requestData['bills'][$reportPositionId][$key]);
                                $value->setModifiedBy($account);
                                $value->getReportPosition()->setModifiedBy($account);
                                if ($value->getParentReportPositionValue() !== null) {
                                    $value->setParentReportPositionValue($value->getParentReportPositionValue());
                                } else {
                                    $value->setParentReportPositionValue($value);
                                }

                                unset($requestData['bills'][$reportPositionId][$key]);
                                if (count($requestData['bills'][$reportPositionId]) == 0) {
                                    unset($requestData['bills'][$reportPositionId]);
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

                        if (isset($requestData['bills'][$reportPositionId]['name'])) {
                            /** @var AccountingPosition $accountingPosition */
                            $accountingPosition = $this->em->getRepository(AccountingPosition::class)->find($requestData['bills'][$reportPositionId]['name']);
                            if (isset($accountingPosition) && $value->getReportPosition()->getAccountingPosition()->getId() != $accountingPosition->getId()) {
                                $value->getReportPosition()->setAccountingPosition($accountingPosition);
                                $value->getReportPosition()->setModifiedBy($account);
                                $value->getReportPosition()->setParentReportPosition($value->getReportPosition());
                            }
                        }

                    } else {
                        // all bills were removed
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

            if (isset($requestData['bills']) && count($requestData['bills']) > 0) {
                $this->addPositions($requestData['bills'], $newReport, $this->tokenStorage->getToken()->getUser());
            }
        }
    }

    /**
     * @param array $billsData
     * @param Report $report
     * @param Account $account
     */
    public function addPositions(array $billsData, Report $report, Account $account)
    {
        if (count($billsData) > 0) {
            /** @var FlexParamRepository $flexparamRepository */
            $flexparamRepository = $this->em->getRepository(FlexParam::class);

            foreach ($billsData as $bill) {
                if (isset($bill['receiver']) && isset($bill['amount']) && isset($bill['tip'])) {
                    /** @var AccountingPosition $accountingPosition */
                    $accountingPosition = $this->em->getRepository(AccountingPosition::class)->find($bill['name']);

                    $billReceiverFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition, 'receiver', 'frontoffice');
                    $billAmountFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition, 'amount', 'frontoffice');
                    $billTipFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition, 'tip', 'frontoffice');

                    $reportPosition = new ReportPosition();
                    $reportPosition->setAccountingPosition($accountingPosition);
                    $reportPosition->setModifiedBy($account);

                    $reportPositionValueReceiver = new ReportPositionValue();
                    $reportPositionValueReceiver->setValue($bill['receiver']);
                    $reportPositionValueReceiver->setParameter($billReceiverFlexParam);
                    $reportPositionValueReceiver->setModifiedBy($account);
                    $reportPositionValueReceiver->setSequence(1);

                    $reportPositionValueAmount = new ReportPositionValue();
                    $reportPositionValueAmount->setValue($bill['amount']);
                    $reportPositionValueAmount->setParameter($billAmountFlexParam);
                    $reportPositionValueAmount->setModifiedBy($account);
                    $reportPositionValueAmount->setSequence(2);

                    $reportPositionValueTip = new ReportPositionValue();
                    $reportPositionValueTip->setValue($bill['tip']);
                    $reportPositionValueTip->setParameter($billTipFlexParam);
                    $reportPositionValueTip->setModifiedBy($account);
                    $reportPositionValueTip->setSequence(3);

                    $reportPosition->addReportPositionValue($reportPositionValueReceiver);
                    $reportPosition->addReportPositionValue($reportPositionValueAmount);
                    $reportPosition->addReportPositionValue($reportPositionValueTip);

                    $report->addReportPosition($reportPosition);
                }
            }
        }
    }
}
