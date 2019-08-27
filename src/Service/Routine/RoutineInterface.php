<?php

namespace App\Service\Routine;

use App\Component\Api\Response;
use App\Entity\Facility;
use App\Entity\Report;

interface RoutineInterface
{
    /**
     * @return mixed
     */
    public function getName();

    /**
     * @return mixed
     */
    public function getParamTemplate();

    /**
     * @return mixed
     */
    public function getAccountingPositionsTemplate();

    /**
     * @return mixed
     */
    public function getOverlayTemplate();

    /**
     * @param Facility|null $facility
     * @param $params
     * @return Response|null
     */
    public function testCallApi(Facility $facility = null, $params): ?Response;

    /**
     * @return RoutineProcessorInterface|null
     */
    public function getBackofficerProcessor(): ?RoutineProcessorInterface;

    /**
     * @return RoutineProcessorInterface|null
     */
    public function getCashierProcessor(): ?RoutineProcessorInterface;

    /**
     * @param Report $report
     * @param array $requestData
     * @return mixed
     */
    public function saveCashier(Report $report, array $requestData);

    /**
     * @param Report $report
     * @param array $requestData
     * @return mixed
     */
    public function saveBackofficer(Report $report, array $requestData);
}
