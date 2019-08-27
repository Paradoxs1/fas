<?php

namespace App\Service\Routine\DataCollector;

use App\Entity\Report;


interface RmaDataCollectorDecoratorIntrface
{
    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $parts
     */
    public function getSales(Report $report, array $reports, array &$data, array &$parts): void;

    /**
     * @param array $reports
     * @param array $data
     * @param array $parts
     */
    public function getIssuedVouchers(array $reports, array &$data, array &$parts): void;

    /**
     * @param array $reports
     * @param array $data
     * @param array $parts
     */
    public function getBills(array $reports, array &$data, array &$parts): void;

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getExpenses(array $reports, array &$data, array $requestData): void;
}
