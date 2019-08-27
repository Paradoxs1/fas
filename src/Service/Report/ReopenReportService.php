<?php

namespace App\Service\Report;

use App\Entity\Report;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;

class ReopenReportService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * ReopenReportService constructor.
     * @param EntityManagerInterface $em
     * @param ReportService $reportService
     */
    public function __construct(EntityManagerInterface $em, ReportService $reportService)
    {
        $this->em = $em;
        $this->reportService = $reportService;
    }

    /**
     * @param Report $report
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function reopenReport(Report $report): bool
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $parentReport = $report->getParentReport() ?? $report;

            $newReport = $this->reportService->addReport(
                $report->getFacilityLayout(),
                $report->getCreatedBy(),
                $report->getStatementDate(),
                $report->getShifts(),
                null,
                false,
                $report->getType(),
                true,
                 $parentReport
            );

            $this->reportService->clonePositions($report, $newReport);
            $this->em->refresh($report);
            $report->setDeletedAt(new \DateTimeImmutable());

            $this->em->flush();
            $this->em->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            return false;
        }
    }
}
