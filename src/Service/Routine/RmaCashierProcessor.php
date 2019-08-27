<?php

namespace App\Service\Routine;

use App\Entity\Facility;
use App\Entity\Report;
use App\Repository\ReportRepository;
use App\Service\Routine\DataCollector\RmaDataCollectorDecoratorIntrface;
use App\Service\Routine\DataCollector\RmaDataCollectorIntrface;


class RmaCashierProcessor extends RmaProcessor implements RmaRoutineProcessorInterface
{
    /**
     * @var RmaDataCollectorDecoratorIntrface
     */
    private $dataCollectorDecorator;

    /**
     * @var RmaDataCollectorIntrface
     */
    private $dataCollector;

    /**
     * @param RmaDataCollectorIntrface $dataCollector
     */
    public function setDataCollector(RmaDataCollectorIntrface $dataCollector)
    {
        $this->dataCollector = $dataCollector;
    }

    /**
     * @param RmaDataCollectorDecoratorIntrface $dataCollectorDecorator
     */
    public function setDataCollectorDecorator(RmaDataCollectorDecoratorIntrface $dataCollectorDecorator)
    {
        $this->dataCollectorDecorator = $dataCollectorDecorator;
    }

    /**
     * @param Facility $facility
     * @param string $date
     * @param bool $approved
     * @return array
     */
    public function getOverlayData(Facility $facility, string $date = '', array $requestData = [], bool $approved = false): array
    {
        /** @var ReportRepository $reportRepository */
        $reportRepository = $this->em->getRepository(Report::class);
        $reports = $reportRepository->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_CASHIER, false);
        $backofficerReport = $reportRepository->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_BACKOFFICER, $approved);

        $data = [
            'reports' => $reports
        ];

        $this->dataCollector->getCreditCards($backofficerReport[0], $reports, $data);
        $this->dataCollector->getCash($reports, $data);
        $this->dataCollector->getIssuedVouchers($reports, $data);
        $this->dataCollector->getAcceptedVouchers($reports, $data);
        $this->dataCollector->getSales($backofficerReport[0], $reports, $data);
        $this->dataCollector->getBills($reports, $data);
        $this->dataCollector->getExpenses($reports, $data);
        $this->dataCollector->getCigarettes($reports, $data);
        $this->dataCollector->getTips($reports, $data);

        return $data;
    }

    /**
     * @param Report $report
     * @return array
     */
    public function getParts(Report $report): array
    {
        $parts = $data = [];
        /** @var ReportRepository $reportRepository */
        $reportRepository = $this->em->getRepository(Report::class);
        $date = $report->getStatementDate()->format('Y-m-d'). ' 00:00:00';
        $reports = $reportRepository->getReportsByFacilityAndDate($report->getFacilityLayout()->getFacility(), $date, Report::REPORT_TYPE_CASHIER, false);

        $this->dataCollectorDecorator->getSales($report, $reports, $data, $parts);
        $this->dataCollectorDecorator->getIssuedVouchers($reports, $data, $parts);
        $this->dataCollectorDecorator->getBills($reports, $data, $parts);

        return $parts;
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCreditCards(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollector->getCreditCards($report, $reports, $data);
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getAcceptedVouchers(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollector->getAcceptedVouchers($reports, $data);
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getExpenses(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollectorDecorator->getExpenses($reports, $data, $requestData);
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCash(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollector->getCash($reports, $data);
    }

    /**
     * @return string
     */
    public function getSuccessMessage(): string
    {
        return $this->translator->trans('report_api.success');
    }
}
