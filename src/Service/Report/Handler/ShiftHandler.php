<?php

namespace App\Service\Report\Handler;

use App\Entity\Report;

class ShiftHandler implements CategoryReportPositionHandlerInterface
{
    /**
     * @param Report $report
     * @param array $requestData
     * @return bool
     */
    public function checkChanges(Report $report, array $requestData): bool
    {
        if (isset($requestData['shifts']) && $requestData['shifts'] != $report->getShifts()) {
            return true;
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
            $oldReport->setShifts($requestData['shifts']);
        }
    }
}
