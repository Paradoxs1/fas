<?php

namespace App\Service\Report\Handler;


use App\Entity\Report;

interface CategoryReportPositionHandlerInterface
{
    /**
     * @param Report $report
     * @param array $data
     * @return bool
     */
    public function checkChanges(Report $report, array $data): bool;

    /**
     * @param Report $newReport
     * @param Report $oldReport
     * @param array $requestData
     */
    public function applyChanges(Report $newReport, Report $oldReport, array $requestData): void;
}
