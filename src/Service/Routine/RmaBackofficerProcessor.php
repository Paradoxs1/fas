<?php

namespace App\Service\Routine;

use App\Entity\Facility;
use App\Entity\Report;
use App\Service\Routine\DataCollector\RmaDataCollectorDecoratorIntrface;
use App\Service\Routine\DataCollector\RmaDataCollectorIntrface;


class RmaBackofficerProcessor extends RmaProcessor implements RmaRoutineProcessorInterface
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
     * @param array $requestData
     * @return Report $report
     * @throws \Exception
     */
    public function saveOverlayData(Facility $facility, string $date, array $requestData): Report
    {
        /** @var Report $report */
        $report = $this->em->getRepository(Report::class)->findOneBy([
            'statementDate' => new \DateTime($date),
            'deletedAt'     => null,
            'approved'      => true,
            'facilityLayout' => $facility->getFacilityLayouts()->last()
        ]);

        try {
            $this->save($report, $requestData);

            if (!empty($this->getErrorMessages())) {
                $this->session->getFlashBag()->set('danger', $this->getErrorMessages());
            } else {
                $this->session->getFlashBag()->set('success', $this->getSuccessMessage());
            }
        } catch (\Exception $e) {
            $this->session->getFlashBag()->set('danger', $e->getMessage());
        }

        return $report;
    }

    /**
     * @param Report $report
     * @return array
     */
    public function getParts(Report $report): array
    {
        $parts = $data = [];

        $this->dataCollectorDecorator->getSales($report, [], $data, $parts);
        $this->dataCollectorDecorator->getIssuedVouchers([$report], $data, $parts);
        $this->dataCollectorDecorator->getBills([$report], $data, $parts);

        return $parts;
    }

    /**
     * @param Facility $facility
     * @param string $date
     * @param array $requestData
     * @param bool $approved
     * @return array
     */
    public function getOverlayData(Facility $facility, string $date = '', array $requestData = [], bool $approved = false): array
    {
        $data = [];

        $this->dataCollector->getCreditCards(null, [], $data, $requestData);
        $this->dataCollector->getCash([], $data, $requestData, $facility);
        $this->dataCollector->getIssuedVouchers([], $data, $requestData, $facility);
        $this->dataCollector->getAcceptedVouchers([], $data, $requestData, $facility);
        $this->dataCollector->getSales(null, [], $data, $requestData);
        $this->dataCollector->getBills([], $data, $requestData, $facility);
        $this->dataCollector->getExpenses([], $data, $requestData, $facility);
        $this->dataCollector->getCigarettes([], $data, $requestData);
        $this->dataCollector->getTips([], $data, $requestData);

        return $data;
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCreditCards(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollector->getCreditCards(null, [], $data, $requestData);
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getAcceptedVouchers(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollector->getAcceptedVouchers([], $data, $requestData, $report->getFacilityLayout()->getFacility());
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getExpenses(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollector->getExpenses([], $data, $requestData, $report->getFacilityLayout()->getFacility());
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCash(Report $report, array $reports = [], array &$data, array $requestData = [])
    {
        $this->dataCollector->getCash([], $data, $requestData, $report->getFacilityLayout()->getFacility());
    }

    /**
     * @return string
     */
    public function getSuccessMessage(): string
    {
        return $this->translator->trans('report_api.success');
    }
}
