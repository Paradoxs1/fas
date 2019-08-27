<?php

namespace App\Service\Routine\DataCollector;

use App\Entity\Facility;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPositionValue;
use App\Service\FlexParamService;


class RmaDataCollector extends RmaAbstractCollector implements RmaDataCollectorIntrface
{
    /**
     * @param Report|null $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCreditCards(Report $report = null, array $reports = [], array &$data, array $requestData = []): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $values = [];

        if (isset($report)) {
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_CREDIT_CARD_KEY);
        }

        if ($values) {
            /** @var ReportPositionValue $value */
            foreach ($values as $value) {
                $accountingPosition = $value->getReportPosition()->getAccountingPosition();
                $this->summationCreditCards($data, $accountingPosition->getId(), $flexParamRepository, $value->getValue());
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getIssuedVouchers(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPosition = $value->getReportPosition();
                    /** @var FlexParam $accountNo */
                    $accountNo = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'accountNo', 'backoffice');
                    $key = $value->getParameter()->getKey();

                    if ($accountNo) {
                        $data['issued_vouchers'][$reportPosition->getId()]['accountNo'] = $accountNo->getValue();
                    }

                    if ($key == 'number') {
                        $data['issued_vouchers'][$reportPosition->getId()][$key] = $value->getValue();
                    }

                    if ($key == 'amount') {
                        $data['issued_vouchers'][$reportPosition->getId()][$key] = $value->getValue();
                    }
                }

                $param = $flexParamRepository->findOneByAccountingPositionAndType($values[0]->getReportPosition()->getAccountingPosition(), 'addToTotalSalesAmount', 'backoffice');

                if ($param->getValue()) {
                    $data['addTotal'] = true;
                } else {
                    $data['addTotal'] = false;
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getAcceptedVouchers(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPosition = $value->getReportPosition();
                    /** @var FlexParam $accountNo */
                    $accountNo = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'accountNo', 'backoffice');
                    $key = $value->getParameter()->getKey();

                    if ($accountNo) {
                        $data['accepted_vouchers'][$reportPosition->getId()]['accountNo'] = $accountNo->getValue();
                    }

                    if ($key == 'number') {
                        $data['accepted_vouchers'][$reportPosition->getId()][$key] = $value->getValue();
                    }

                    if ($key == 'amount') {
                        $data['accepted_vouchers'][$reportPosition->getId()][$key] = $value->getValue();
                    }
                }
            }
        }
    }

    /**
     * @param Report|null $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getSales(Report $report = null, array $reports = [], array &$data, array $requestData = []): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $values = [];

        if (isset($report)) {
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_SALES_CATEGORY_KEY);
        }

        if ($values) {
            /** @var ReportPositionValue $value */
            foreach ($values as $value) {
                $accountingPosition = $value->getReportPosition()->getAccountingPosition();
                $this->transformSales($data, $flexParamRepository, $accountingPosition->getId(), $value->getValue());
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getBills(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_BILL_KEY);
            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPosition = $value->getReportPosition();
                    /** @var FlexParam $accountNo */
                    $accountNo = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'accountNo', 'backoffice');
                    $tipAccountNo = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'tipAccountNo', 'backoffice');

                    $key = $value->getParameter()->getKey();

                    if ($accountNo) {
                        $data['bills'][$reportPosition->getId()]['accountNo'] = $accountNo->getValue();
                    }

                    if ($tipAccountNo) {
                        $data['bills'][$reportPosition->getId()]['tipAccountNo'] = $tipAccountNo->getValue();
                    }

                    if ($key == 'receiver') {
                        $data['bills'][$reportPosition->getId()][$key] = $value->getValue();
                    }

                    if ($key == 'amount') {
                        $data['bills'][$reportPosition->getId()][$key] = $value->getValue();
                    }

                    if ($key == 'tip') {
                        $data['bills'][$reportPosition->getId()][$key] = $value->getValue();
                    }
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getExpenses(array $reports = [],array &$data, array $requestData = [], Facility $facility = null): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_EXPENSES_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPosition = $value->getReportPosition();
                    /** @var FlexParam $accountNo */
                    $accountNo = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'accountNo', 'backoffice');
                    $catalogNumber = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'catalogNumber', 'frontoffice');
                    $key = $value->getParameter()->getKey();

                    if ($accountNo) {
                        $data['expenses'][$reportPosition->getId()]['accountNo'] = $accountNo->getValue();
                    }

                    if ($key == 'amount') {
                        $data['expenses'][$reportPosition->getId()][$key] = $value->getValue();
                    }
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getCash(array $reports = [],array &$data, array $requestData = [], Facility $facility = null): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_CASH_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPosition = $value->getReportPosition();
                    /** @var FlexParam $accountNo */
                    $accountNo = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'accountNo', 'backoffice');
                    $name = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'name', 'backoffice');
                    $key = $value->getParameter()->getKey();

                    if ($accountNo) {
                        $data['cash']['accountNo'] = $accountNo->getValue();
                    }

                    if ($name) {
                        $data['cash']['name'] = $name->getValue();
                    }

                    if ($key == 'amount') {
                        if (isset($data['cash']['sum'])) {
                            $data['cash']['sum'] += $value->getValue();
                        } else {
                            $data['cash']['sum'] = $value->getValue();
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCigarettes(array $reports = [], array &$data, array $requestData = []): void
    {
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_CIGARETTES_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    if (isset($data['cigarettes']['sum'])) {
                        $data['cigarettes']['sum'] += $value->getValue();
                    } else {
                        $data['cigarettes']['sum'] = $value->getValue();
                    }
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getTips(array $reports = [], array &$data, array $requestData = []): void
    {
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

        foreach ($reports as $report) {
            $values = $reportPositionValueRepository->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_TIP_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $reportPosition = $value->getReportPosition();
                    /** @var FlexParam $accountNo */
                    $param = $flexParamRepository->findOneByAccountingPositionAndType($reportPosition->getAccountingPosition(), 'name', 'backoffice');

                    if (isset($data['tips'][$param->getValue()])) {
                        $data['tips'][$param->getValue()] += $value->getValue();
                    } else {
                        $data['tips'][$param->getValue()] = $value->getValue();
                    }
                }
            }
        }
    }
}
