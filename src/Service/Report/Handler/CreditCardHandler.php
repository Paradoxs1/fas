<?php

namespace App\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\AccountingPosition;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionGroup;
use App\Entity\ReportPositionValue;
use App\Service\FlexParamService;
use App\Service\Report\CategoryReportPositionHandlerComposite;
use App\Service\ReportPositionGroupService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CreditCardHandler implements CategoryReportPositionHandlerInterface
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
     * @var ReportPositionGroupService
     */
    private $reportPositionGroupService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var int
     */
    private static $sequence = 1;

    /**
     * CreditCardHandler constructor.
     * @param ReportService $reportService
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param ReportPositionGroupService $reportPositionGroupService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ReportService $reportService,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        ReportPositionGroupService $reportPositionGroupService,
        TranslatorInterface $translator
    )
    {
        $this->reportService = $reportService;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->reportPositionGroupService = $reportPositionGroupService;
        $this->translator = $translator;
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @return bool
     */
    public function checkChanges(Report $report, array $requestData): bool
    {
        $paymentMethodData = $this->reportService->getCreditCards($report->getFacilityLayout(), $report);

        if ($paymentMethodData) {
            if (isset($requestData['credit-cards'])) {
                // check count of terminals
                if (count($paymentMethodData) != count($requestData['credit-cards'])) {
                    return true;
                }

                foreach ($paymentMethodData as $terminalId => $methodData) {
                    if (!isset($requestData['credit-cards'][$terminalId])) {
                        return true;
                    }
                    if (count($methodData) != count($requestData['credit-cards'][$terminalId])) {
                        return true;
                    }
                    foreach ($methodData as $accountingPosition => $data) {
                        if (!isset($requestData['credit-cards'][$terminalId][$accountingPosition])) {
                            return true;
                        }
                        if (current($requestData['credit-cards'][$terminalId][$accountingPosition])) {
                            foreach ($data['data'] as $amount) {
                                if (count($data['data']) != count($requestData['credit-cards'][$terminalId][$accountingPosition])) {
                                    return true;
                                }
                                $incomeAmount = CategoryReportPositionHandlerComposite::formatAmount(current($requestData['credit-cards'][$terminalId][$accountingPosition]));
                                if ($incomeAmount == $amount) {
                                    next($requestData['credit-cards'][$terminalId][$accountingPosition]);
                                } else {
                                    return true;
                                }
                                self::$sequence++;
                            }
                        } else {
                            return true;
                        }
                    }
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
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_CREDIT_CARD_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $terminalId = $value->getReportPositionGroup()->getId();
                    $accountingPositionId = $value->getReportPosition()->getAccountingPosition()->getId();
                    $reportPositionId = $value->getReportPosition()->getId();
                    if (isset($requestData['credit-cards'])) {
                        if (isset($requestData['credit-cards'][$terminalId][$accountingPositionId][$reportPositionId])) {
                            if (isset($requestData['credit-cards'][$terminalId][$accountingPositionId][$reportPositionId]) && $requestData['credit-cards'][$terminalId][$accountingPositionId][$reportPositionId] == $value->getValue()) {
                                unset($requestData['credit-cards'][$terminalId][$accountingPositionId][$reportPositionId]);
                                if (count($requestData['credit-cards'][$terminalId][$accountingPositionId]) == 0) {
                                    unset($requestData['credit-cards'][$terminalId][$accountingPositionId]);
                                }
                                if (count($requestData['credit-cards'][$terminalId]) == 0) {
                                    unset($requestData['credit-cards'][$terminalId]);
                                }
                            } else {
                                // position was changed
                                $value->setValue(CategoryReportPositionHandlerComposite::formatAmount($requestData['credit-cards'][$terminalId][$accountingPositionId][$reportPositionId]));
                                $value->setModifiedBy($this->tokenStorage->getToken()->getUser());
                                $value->getReportPosition()->setModifiedBy($this->tokenStorage->getToken()->getUser());
                                if ($value->getParentReportPositionValue() !== null) {
                                    $value->setParentReportPositionValue($value->getParentReportPositionValue());
                                } else {
                                    $value->setParentReportPositionValue($value);
                                }

                                unset($requestData['credit-cards'][$terminalId][$accountingPositionId][$reportPositionId]);
                                if (count($requestData['credit-cards'][$terminalId][$accountingPositionId]) == 0) {
                                    unset($requestData['credit-cards'][$terminalId][$accountingPositionId]);
                                }
                                if (count($requestData['credit-cards'][$terminalId]) == 0) {
                                    unset($requestData['credit-cards'][$terminalId]);
                                }
                            }
                        } else {
                            // position was removed
                            $value->setModifiedBy($this->tokenStorage->getToken()->getUser());
                            $value->setDeletedAt(new \DateTimeImmutable());
                            $value->getReportPosition()->setModifiedBy($this->tokenStorage->getToken()->getUser());
                            $value->getReportPosition()->setDeletedAt(new \DateTimeImmutable());
                            if ($value->getParentReportPositionValue() !== null) {
                                $value->setParentReportPositionValue($value->getParentReportPositionValue());
                            } else {
                                $value->setParentReportPositionValue($value);
                            }

                            unset($requestData['credit-cards'][$terminalId][$accountingPositionId][$reportPositionId]);
                            if (isset($requestData['credit-cards'][$terminalId][$accountingPositionId]) && count($requestData['credit-cards'][$terminalId][$accountingPositionId]) == 0) {
                                unset($requestData['credit-cards'][$terminalId][$accountingPositionId]);
                            }
                            if (isset($requestData['credit-cards'][$terminalId]) && count($requestData['credit-cards'][$terminalId]) == 0) {
                                unset($requestData['credit-cards'][$terminalId]);
                            }
                        }
                    } else {
                        // all positions were removed
                        $value->setModifiedBy($this->tokenStorage->getToken()->getUser());
                        $value->setDeletedAt(new \DateTimeImmutable());
                        $value->getReportPosition()->setModifiedBy($this->tokenStorage->getToken()->getUser());
                        $value->getReportPosition()->setDeletedAt(new \DateTimeImmutable());
                        if ($value->getParentReportPositionValue() !== null) {
                            $value->setParentReportPositionValue($value->getParentReportPositionValue());
                        } else {
                            $value->setParentReportPositionValue($value);
                        }
                    }
                    self::$sequence++;
                }

                if (count($requestData['credit-cards']) > 0) {
                    $this->addPositions($requestData['credit-cards'], $newReport, $this->tokenStorage->getToken()->getUser());
                }
            }
        }
    }

    /**
     * @param array $ccData
     * @param Report $report
     * @param Account $account
     */
    public function addPositions(array $ccData, Report $report, Account $account)
    {
        foreach ($ccData as $terminalId => $methodData) {
            $terminalName = $this->translator->trans('report.terminal');

            $reportPositionGroup = $this->em->getRepository(ReportPositionGroup::class)->find($terminalId);
            $reportPositionGroup = $reportPositionGroup != null ? $reportPositionGroup : new ReportPositionGroup();
            $reportPositionGroup->setName($terminalName);

            foreach ($methodData as $accountingPositionId => $data) {
                foreach ($data as $i => $amount) {
                    $reportPosition = new ReportPosition();
                    /** @var AccountingPosition $accountingPosition */
                    $accountingPosition = $this->em->getRepository(AccountingPosition::class)->find($accountingPositionId);
                    $reportPosition->setAccountingPosition($accountingPosition);
                    $reportPosition->setModifiedBy($account);

                    $reportPositionValue = new ReportPositionValue();
                    $creditCardValueFlexParam = $this->em->getRepository(FlexParam::class)->findOneByAccountingPositionAndType($accountingPosition, 'value', 'frontoffice');
                    $reportPositionValue->setValue(CategoryReportPositionHandlerComposite::formatAmount($amount));
                    $reportPositionValue->setParameter($creditCardValueFlexParam);
                    $reportPositionValue->setModifiedBy($account);
                    $reportPositionValue->setReportPositionGroup($reportPositionGroup);
                    $reportPositionValue->setSequence(self::$sequence);

                    $reportPosition->addReportPositionValue($reportPositionValue);
                    $report->addReportPosition($reportPosition);
                    self::$sequence++;
                }
            }
        }
    }
}
