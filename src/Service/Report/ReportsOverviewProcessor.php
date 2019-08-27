<?php

namespace App\Service\Report;

use App\Component\Api\Api;
use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Repository\ReportRepository;
use App\Service\FlexParamService;
use App\Service\ReportService;
use App\Service\Routine\RoutineRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ReportsOverviewProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ReportService
     */
    private $reportSevice;

    /**
     * @var
     */
    private $routineRegistry;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ReportsOverviewProcessor constructor.
     * @param EntityManagerInterface $em
     * @param ReportService $reportService
     * @param RoutineRegistry $routineRegistry
     * @param SessionInterface $session
     * @param UrlGeneratorInterface $router
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityManagerInterface $em,
        ReportService $reportService,
        RoutineRegistry $routineRegistry,
        SessionInterface $session,
        UrlGeneratorInterface $router,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->reportSevice = $reportService;
        $this->routineRegistry = $routineRegistry;
        $this->session = $session;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * @param Facility $facility
     * @param string $date
     * @param bool $approved
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function process(Facility $facility, string $date, bool $approved): array
    {
        /** @var ReportRepository $reportRepository */
        $reportRepository = $this->em->getRepository(Report::class);
        $reports = $reportRepository->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_CASHIER, false);
        $backofficerReport = $reportRepository->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_BACKOFFICER, $approved);

        // For a approved backofficer  report
        if (!$reports) {
            $reports = $reportRepository->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_BACKOFFICER, true);
        }

        $data = [
            'reports' => $reports
        ];

        $this->getShifts($reports, $data);
        $this->getComments($reports, $data);
        $this->getAccounts($reports, $data);
        $this->getCreditCards($backofficerReport, $reports, $data);
        $this->getVouchers($reports, $data);
        $this->getSales($backofficerReport, $reports, $data);
        $this->getBills($reports, $data);
        $this->getDues($facility, $backofficerReport, $reports, $data, $date);
        $this->getExpenses($reports, $data);

        return $data;
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getShifts(array $reports, array &$data) {
        foreach ($reports as $report) {
            $data['shifts'][$report->getId()] = $report->getShifts();
        }
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getComments(array $reports, array &$data)
    {
        foreach ($reports as $report) {
            $data['comments'][$report->getId()] = $this->reportSevice->getComment($report->getFacilityLayout(), $report);
        }
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getAccounts(array $reports, array &$data)
    {
        foreach ($reports as $report) {
            $data['accounts'][$report->getId()] = $report->getCreatedBy()->getPerson()->getFirstName() . ' ' . $report->getCreatedBy()->getPerson()->getLastName();
        }
    }

    /**
     * @param array $backofficerReport
     * @param array $reports
     * @param array $data
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCreditCards(array $backofficerReport, array $reports, array &$data)
    {
        $ccBackofficerTotal = $ccCashierTotal = 0;
        $backofficerData = [];

        /** @var Report $report */
        foreach ($reports as $report) {
            $ccData = $this->reportSevice->getCreditCards($report->getFacilityLayout(), $report);
            $total = 0;
            $tmp = [];

            foreach ($ccData as $terminalId => $paymentsData) {
                foreach ($paymentsData as $accountingPositionId => $paymentDataItem) {
                    foreach ($paymentDataItem['data'] as $id => $value) {
                        if (!isset($tmp[$paymentDataItem['name']])) {
                            $tmp[$paymentDataItem['name']] = $value;
                        } else {
                            $tmp[$paymentDataItem['name']] += $value;
                        }
                        $total += $value;
                    }
                }
            }

            $data['credit_cards']['cashier_data'][$report->getId()] = [
                'data' => $tmp,
                'total' => $total
            ];

            $ccCashierTotal += $total;

            // check parent report
            $parentReport = $report->getParentReport();
            if ($parentReport) {
                $ccData = $this->reportSevice->getCreditCards($parentReport->getFacilityLayout(), $parentReport);
                $total = 0;
                $tmp = [];

                foreach ($ccData as $terminalId => $paymentsData) {
                    foreach ($paymentsData as $accountingPositionId => $paymentData) {
                        foreach ($paymentData['data'] as $key => $value) {
                            if (!isset($tmp[$paymentData['name']])) {
                                $tmp[$paymentData['name']] = $value;
                            } else {
                                $tmp[$paymentData['name']] += $value;
                            }
                        }
                        foreach ($paymentData['data'] as $key => $value) {
                            $total += $value;
                        }
                    }
                }

                $data['credit_cards']['parent_cashier_data'][$report->getId()] = [
                    'data' => $tmp,
                    'total' => $total
                ];
            }
        }

        $data['credit_cards']['cashier_total'] = $ccCashierTotal;
        $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($backofficerReport[0], FlexParamService::ACCOUNTING_CATEGORY_CREDIT_CARD_KEY);

        foreach ($values as $value) {
            $reportPosition = $value->getReportPosition();

            if ($reportPosition) {
                $flexParam = $this->em->getRepository(FlexParam::class)->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'name', 'backoffice');

                if (isset($backofficerData[$flexParam->getValue()])) {
                    $backofficerData[$flexParam->getValue()] += $value->getValue();
                } else {
                    $backofficerData[$flexParam->getValue()] = $value->getValue();
                }
            }
            $ccBackofficerTotal += (float) str_replace("'",'', $value->getValue());
        }
        $data['credit_cards']['backofficer_data'] = $backofficerData;

        $data['credit_cards']['backofficer_total'] = $ccBackofficerTotal;
        $data['credit_cards']['diff'] = $ccCashierTotal - $ccBackofficerTotal;
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getVouchers(array $reports, array &$data)
    {
        $this->getIssuedVouchers($reports, $data);
        $this->getAcceptedVouchers($reports, $data);

        $data['vouchers']['totalAllVouchers'] = 0;
        foreach ($reports as $report) {
            $totalAllVouchers = 0;
            if (isset($data['accepted_vouchers']['data'][$report->getId()]['total'])) {
                if (isset($data['issued_vouchers']['data'][$report->getId()]['total']) && $data['issued_vouchers']['data'][$report->getId()]['addTotal']) {
                    $total = $data['accepted_vouchers']['data'][$report->getId()]['total'] - $data['issued_vouchers']['data'][$report->getId()]['total'];
                    $totalAllVouchers += $total;
                    $data['vouchers']['total'][$report->getId()] = $total;
                } else {
                    $total = $data['accepted_vouchers']['data'][$report->getId()]['total'];
                    $data['vouchers']['total'][$report->getId()] = $total;
                    $totalAllVouchers += $total;
                }
            } else {
                if (isset($data['issued_vouchers']['data'][$report->getId()]['total']) && $data['issued_vouchers']['data'][$report->getId()]['addTotal']) {
                    $total = 0 - $data['issued_vouchers']['data'][$report->getId()]['total'];
                    $data['vouchers']['total'][$report->getId()] = $total;
                    $totalAllVouchers += $total;
                }
            }

            $data['vouchers']['totalAllVouchers'] += $totalAllVouchers;
        }
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getIssuedVouchers(array $reports, array &$data)
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY);

            if ($values) {
                $total = $parentTotal = 0;
                $arr = [];
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    /** @var ReportPosition $reportPosition */
                    $reportPosition = $value->getReportPosition();
                    if ($value->getParameter()->getKey() == 'amount') {
                        $total += (float) str_replace("'",'', $value->getValue());
                        $parentValue = $value->getParentReportPositionValue();
                        $arr[$reportPosition->getId()][$value->getParameter()->getKey()] = $value->getValue();

                        if ($parentValue) {
                            $arr[$reportPosition->getId()]['old_amount'] = $parentValue->getValue();
                            $parentTotal += (float) str_replace("'",'', $parentValue->getValue());
                        }
                    } else {
                        $tmp = [
                            'reportId' => $report->getId(),
                            'number' => $value->getValue()
                        ];

                        $parentValue = $value->getParentReportPositionValue();
                        if ($parentValue) {
                            $tmp = array_merge($tmp, ['old_number' => $parentValue->getValue()]);
                        }

                        if (!$parentValue && $value->getModifiedBy() && !$value->getDeletedAt()) {
                            $tmp = array_merge($tmp, ['new' => true]);
                        }

                        $data['issued_vouchers']['numbers'][] = $tmp;

                    }
                    $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();
                }
                $data['issued_vouchers']['data'][$report->getId()]['data'] = array_values($arr);
                $data['issued_vouchers']['data'][$report->getId()]['total'] = $total;
                $data['issued_vouchers']['data'][$report->getId()]['parentTotal'] = $parentTotal;

                $param = $flexParamRepository->findOneByAccountingPositionAndType($values[0]->getReportPosition()->getAccountingPosition(), 'addToTotalSalesAmount', 'backoffice');

                if ($param->getValue()) {
                    $data['issued_vouchers']['data'][$report->getId()]['addTotal'] = true;
                    $data['issued_vouchers']['addTotal'] = true;
                } else {
                    $data['issued_vouchers']['data'][$report->getId()]['addTotal'] = false;
                    $data['issued_vouchers']['addTotal'] = false;
                }
            }

            $removedValues = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY, true);

            if ($removedValues) {
                /** @var ReportPositionValue $value */
                foreach ($removedValues as $value) {
                    $reportPosition = $value->getReportPosition();
                    $data['issued_vouchers']['removedData'][$reportPosition->getReport()->getId()][$reportPosition->getId()][$value->getParameter()->getKey()] = $value->getValue();
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getAcceptedVouchers(array $reports, array &$data)
    {
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);
        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY);
            $total = $parentTotal = 0;
            $arr = [];
            /** @var ReportPositionValue $value */
            foreach ($values as $value) {
                if ($value->getParameter()->getKey() == 'amount') {
                    $total += (float) str_replace("'",'', $value->getValue());
                    $parentValue = $value->getParentReportPositionValue();

                    $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();

                    if ($parentValue) {
                        $arr[$value->getReportPosition()->getId()]['old_amount'] = $parentValue->getValue();
                        $parentTotal += (float) str_replace("'",'', $parentValue->getValue());
                    }
                } else {
                    $tmp = [
                        'reportId' => $report->getId(),
                        'number' => $value->getValue()
                    ];

                    $parentValue = $value->getParentReportPositionValue();
                    if ($parentValue) {
                        $tmp = array_merge($tmp, ['old_number' => $parentValue->getValue()]);
                    }

                    if (!$parentValue && $value->getModifiedBy() && !$value->getDeletedAt()) {
                        $tmp = array_merge($tmp, ['new' => true]);
                    }

                    $data['accepted_vouchers']['numbers'][] = $tmp;
                }
                $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();
            }
            $data['accepted_vouchers']['data'][$report->getId()]['data'] = array_values($arr);
            $data['accepted_vouchers']['data'][$report->getId()]['total'] = $total;
            $data['accepted_vouchers']['data'][$report->getId()]['parentTotal'] = $parentTotal;

            $removedValues = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY, true);

            if ($removedValues) {
                /** @var ReportPositionValue $value */
                foreach ($removedValues as $value) {
                    $reportPosition = $value->getReportPosition();
                    $data['accepted_vouchers']['removedData'][$reportPosition->getReport()->getId()][$reportPosition->getId()][$value->getParameter()->getKey()] = $value->getValue();
                }
            }
        }
    }

    /**
     * @param array $backofficerReport
     * @param array $reports
     * @param array $data
     */
    private function getSales(array $backofficerReport, array $reports, array &$data)
    {
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);
        $sales = $this->reportSevice->getSales($backofficerReport[0]->getFacilityLayout(), $backofficerReport[0]);
        $backofficerTotal = $cashierTotal = 0;
        $trigger = false;

        foreach ($sales as $accountingPosition => $sale) {
            $backofficerTotal += $sale['value'];
            $data['sales']['names'][$accountingPosition] = [
                'name' => $sale['name'],
                'amount' => $sale['value']
            ];
        }

        $data['sales']['backofficer_total'] = $backofficerTotal;

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_TOTAL_SALES_KEY);

            if ($values) {
                $trigger = true;
                $data['sales']['data'][$report->getId()] = $values[0]->getValue();

                $parentValue = $values[0]->getParentReportPositionValue();
                if ($parentValue) {
                    $data['sales']['data'][$report->getId()] = $values[0]->getValue();
                    $data['sales']['old_data'][$report->getId()] = $parentValue->getValue();
                }
                $cashierTotal += (float) str_replace("'",'', $values[0]->getValue());
            }
        }

        if ($trigger) {
            $data['sales']['cashier_total'] = $cashierTotal;
            $data['sales']['difference'] = $cashierTotal - $backofficerTotal;
        }
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getBills(array $reports, array &$data)
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_BILL_KEY);
            if ($values) {
                $total = $parentTotal = $totalTip = 0;
                $arr = [];
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    if ($value->getParameter()->getKey() == 'amount') {
                        $total += (float) str_replace("'",'', $value->getValue());
                        $parentValue = $value->getParentReportPositionValue();
                        $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();

                        if ($parentValue) {
                            $arr[$value->getReportPosition()->getId()]['old_amount'] = $parentValue->getValue();
                            $parentTotal += (float) str_replace("'",'', $parentValue->getValue());
                        }
                    } elseif ($value->getParameter()->getKey() == 'tip') {
                        $totalTip += (float) str_replace("'",'', $value->getValue());
                        $parentValue = $value->getParentReportPositionValue();
                        $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();

                        if ($parentValue) {
                            $arr[$value->getReportPosition()->getId()]['old_tip'] = $parentValue->getValue();
                            $parentTotal += (float) str_replace("'",'', $parentValue->getValue());
                        }
                    } else {
                        $param = $flexParamRepository->findOneByAccountingPositionAndType(
                            $value->getReportPosition()->getAccountingPosition(),
                            'name',
                            'backoffice'
                        );

                        $tmp = [
                            'reportId' => $report->getId(),
                            'name' => $value->getValue(),
                            'bill_name' => $param->getValue()
                        ];

                        $parentValue = $value->getParentReportPositionValue();
                        if ($parentValue) {
                            $tmp = array_merge($tmp, ['old_name' => $parentValue->getValue()]);
                        }
                        $parentPosition = $value->getReportPosition()->getParentReportPosition();
                        if ($parentPosition) {
                            if ($parentPosition->getAccountingPosition()->getId() != $value->getReportPosition()->getAccountingPosition()->getId()) {
                                $param = $flexParamRepository->findOneByAccountingPositionAndType(
                                    $parentPosition->getAccountingPosition(),
                                    'name',
                                    'backoffice'
                                );

                                $tmp = array_merge($tmp, ['old_bill_name' => $param->getValue()]);
                            }
                        }

                        if (!$parentValue && $value->getModifiedBy() && !$value->getDeletedAt()) {
                            $tmp = array_merge($tmp, ['new' => true]);
                        }

                        $data['bills']['receivers'][] = $tmp;


                    }
                    $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();
                }
                $data['bills']['data'][$report->getId()]['data'] = array_values($arr);
                $data['bills']['data'][$report->getId()]['total'] = $total;
                $data['bills']['data'][$report->getId()]['parentTotal'] = $parentTotal;
                $data['bills']['data'][$report->getId()]['tipsTotal'] = $totalTip;
            }

            $removedValues = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_BILL_KEY, true);

            if ($removedValues) {
                /** @var ReportPositionValue $value */
                foreach ($removedValues as $value) {
                    $reportPosition = $value->getReportPosition();
                    $data['bills']['removedData'][$reportPosition->getReport()->getId()][$reportPosition->getId()][$value->getParameter()->getKey()] = $value->getValue();
                }
            }
        }

        if (isset($data['bills'])) {
            $data['bills']['totalAllBills'] = $data['bills']['totalAllTips'] = 0;
            foreach ($reports as $report) {
                $totalAllBills = $totalAllTips = 0;
                if (isset($data['bills']['data'][$report->getId()]['total'])) {
                    $total = $data['bills']['data'][$report->getId()]['total'];
                    $tipsTotal = $data['bills']['data'][$report->getId()]['tipsTotal'];
                    $data['bills']['total'][$report->getId()] = $total;
                    $totalAllBills += $total;
                    $totalAllTips += $tipsTotal;
                }
                $data['bills']['totalAllBills'] += $totalAllBills;
                $data['bills']['totalAllTips'] += $totalAllTips;
            }
        }
    }

    /**
     * @param Facility $facility
     * @param array $backofficerReport
     * @param array $reports
     * @param array $data
     * @param string $date
     */
    private function getDues(Facility $facility, array $backofficerReport, array $reports, array &$data, string $date)
    {
        $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($backofficerReport[0], FlexParamService::ACCOUNTING_CATEGORY_CASH_KEY);

        $accountRepository = $this->em->getRepository(Account::class);
        $reportRepository = $this->em->getRepository(Report::class);

        foreach ($reports as $report) {
            foreach ($values as $value) {
                if ($value->getParameter()->getKey() == 'cashier') {
                    $reportPositionId = $value->getReportPosition()->getId();
                    foreach ($values as $positionValue) {
                        if ($positionValue->getParameter()->getKey() == 'amount' && $positionValue->getReportPosition()->getId() == $reportPositionId) {
                            $account = $accountRepository->find($value->getValue());
                            $cashierReport = $reportRepository->findOneByFacilityUserStatementDate($facility, $account, $date);
                            if ($cashierReport) {
                                $data['dues']['backofficer_data'][$cashierReport->getId()] = str_replace("'",'', $positionValue->getValue());
                            }
                        }
                    }
                }
            }

            $cash = $this->reportSevice->getCash($report->getFacilityLayout(), $report);
            $cashIncome = $cash['value'];

            $tips = $this->reportSevice->getTips($report->getFacilityLayout(), $report);
            $tipsTotal = 0;
            if ($tips) {
                foreach ($tips as $tip) {
                    if (isset($tip['value'])) {
                        $data['dues']['cashier_data'][$report->getId()][$tip['name']]['value'] = $tip['value'];
                        /** @var ReportPositionValue $reportPositionValue */
                        $reportPositionValue = $this->em->getRepository(ReportPositionValue::class)->find($tip['reportPositionValue']);
                        $parentValue = $reportPositionValue->getParentReportPositionValue();
                        if ($parentValue) {
                            $data['dues']['cashier_data'][$report->getId()][$tip['name']]['old_value'] = str_replace("'", '', $parentValue->getValue());
                        }

                        $tipsTotal += $tip['value'];
                    }
                }
            }

            $cigarettes = $this->reportSevice->getCigarettes($report->getFacilityLayout(), $report);
            if (isset($cigarettes['value'])) {
                $data['dues']['cashier_data'][$report->getId()]['Cigarettes']['value'] = $cigarettes['value'];
                /** @var ReportPositionValue $reportPositionValue */
                $reportPositionValue = $this->em->getRepository(ReportPositionValue::class)->find($cigarettes['reportPositionValue']);
                $parentValue = $reportPositionValue->getParentReportPositionValue();
                if ($parentValue) {
                    $data['dues']['cashier_data'][$report->getId()]['Cigarettes']['old_value'] = $parentValue->getValue();
                }
            }
            $data['dues']['cash_income'][$report->getId()] = $cashIncome;
        }
    }

    /**
     * @param array $reports
     * @param array $data
     */
    private function getExpenses(array $reports, array &$data)
    {
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);
        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_EXPENSES_KEY);
            if ($values) {
                $total = $parentTotal = 0;
                $arr = [];
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    if ($value->getParameter()->getKey() == 'amount') {
                        $total += (float) str_replace("'",'', $value->getValue());
                        $parentValue = $value->getParentReportPositionValue();
                        $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();

                        if ($parentValue) {
                            $arr[$value->getReportPosition()->getId()]['old_amount'] = $parentValue->getValue();
                            $parentTotal += (float) str_replace("'",'', $parentValue->getValue());
                        }
                    } elseif($value->getParameter()->getKey() != 'catalogNumber') {
                        $tmp = [
                            'id' => $value->getReportPosition()->getId(),
                            'reportId' => $report->getId(),
                            'name' => $value->getValue()
                        ];

                        $parentValue = $value->getParentReportPositionValue();
                        if ($parentValue) {
                            $tmp = array_merge($tmp, ['old_name' => $parentValue->getValue()]);
                        }

                        if (!$parentValue && $value->getModifiedBy() && !$value->getDeletedAt()) {
                            $tmp = array_merge($tmp, ['new' => true]);
                        }

                        $data['expenses']['names'][] = $tmp;

                    }
                    $arr[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();
                }
                $data['expenses']['data'][$report->getId()]['data'] = array_values($arr);
                $data['expenses']['data'][$report->getId()]['total'] = $total;
                $data['expenses']['data'][$report->getId()]['parentTotal'] = $parentTotal;
            }

            $removedValues = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_EXPENSES_KEY, true);

            if ($removedValues) {
                /** @var ReportPositionValue $value */
                foreach ($removedValues as $value) {
                    if ($value->getParameter()->getKey() != 'catalogNumber') {
                        $reportPosition = $value->getReportPosition();
                        $data['expenses']['removedData'][$reportPosition->getReport()->getId()][$reportPosition->getId()][$value->getParameter()->getKey()] = $value->getValue();
                    }
                }
            }
        }

        if (isset($data['expenses'])) {
            $data['expenses']['totalAllExpenses'] = 0;
            foreach ($reports as $report) {
                $totalAllExpenses = 0;
                if (isset($data['expenses']['data'][$report->getId()]['total'])) {
                    $total = $data['expenses']['data'][$report->getId()]['total'];
                    $data['expenses']['total'][$report->getId()] = $total;
                    $totalAllExpenses += $total;
                }
                $data['expenses']['totalAllExpenses'] += $totalAllExpenses;
            }
        }
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @throws \Exception
     */
    public function finalize(Report $report, array $requestData)
    {
        $facility = $report->getFacilityLayout()->getFacility();
        $lastReport = $this->em->getRepository(Report::class)->getLatestApprovedReport($facility);
        $number = ($lastReport && $lastReport[0]->getNumber()) ? $lastReport[0]->getNumber() + 1 : 1;

        $report->setApproved(true);
        $report->setNumber($number);
        $this->em->flush();

        $this->em->getConnection()->beginTransaction();
        try {
            if (isset($requestData['catalog-name'])) {
                $reportPositionRepository = $this->em->getRepository(ReportPosition::class);
                $flexParamRepository = $this->em->getRepository(FlexParam::class);
                foreach ($requestData['catalog-name'] as $reportPositionId => $amount) {
                    /** @var ReportPosition $reportPosition */
                    $reportPosition = $reportPositionRepository->find($reportPositionId);
                    $param = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'catalogNumber', 'frontoffice');

                    if ($reportPosition) {
                        $reportPositionValue = new ReportPositionValue();
                        $reportPositionValue->setReportPosition($reportPosition);
                        $reportPositionValue->setValue(CategoryReportPositionHandlerComposite::formatAmount($amount));
                        $reportPositionValue->setParameter($param);
                        $reportPositionValue->setSequence(3);

                        $this->em->persist($reportPositionValue);
                    }
                }
            }

            $routine = $this->routineRegistry->getRoutine($facility->getRoutine()->getName());
            $routine->saveCashier($report, $requestData);

            if ($routine->getCashierProcessor()) {
                if (!empty($routine->getCashierProcessor()->getErrorMessages())) {
                    $this->session->getFlashBag()->set('danger', $routine->getCashierProcessor()->getErrorMessages());
                } else {
                    $this->session->getFlashBag()->set('success', $routine->getCashierProcessor()->getSuccessMessage());
                }
            }

            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $this->session->getFlashBag()->set('danger', $e->getMessage());
        }
    }
}
