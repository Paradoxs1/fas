<?php

namespace App\Service\Routine;

use App\Entity\Facility;
use App\Entity\Report;
use App\Service\Routine\DataCollector\RmaDataCollectorDecoratorIntrface;
use App\Service\Routine\DataCollector\RmaDataCollectorIntrface;


interface RmaRoutineProcessorInterface extends RoutineProcessorInterface
{
    /**
     * @param RmaDataCollectorIntrface $dataCollector
     * @return mixed
     */
    public function setDataCollector(RmaDataCollectorIntrface $dataCollector);

    /**
     * @param RmaDataCollectorDecoratorIntrface $dataCollectorDecorator
     * @return mixed
     */
    public function setDataCollectorDecorator(RmaDataCollectorDecoratorIntrface $dataCollectorDecorator);

    /**
     * @param Report $report
     * @return array
     */
    public function getParts(Report $report): array;

    /**
     * @param Facility $facility
     * @param string $date
     * @param array $requestData
     * @param bool $approved
     * @return array
     */
    public function getOverlayData(Facility $facility, string $date = '', array $requestData = [], bool $approved = false): array;

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    public function getCreditCards(Report $report, array $reports = [], array &$data, array $requestData = []);

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    public function getAcceptedVouchers(Report $report, array $reports = [], array &$data, array $requestData = []);

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    public function getExpenses(Report $report, array $reports = [], array &$data, array $requestData = []);

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    public function getCash(Report $report, array $reports = [], array &$data, array $requestData = []);
}
