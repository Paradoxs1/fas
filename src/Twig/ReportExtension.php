<?php

namespace App\Twig;

use App\Entity\Facility;
use App\Entity\Report;
use App\Entity\ReportPositionValue;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Extension\AbstractExtension;

class ReportExtension extends AbstractExtension
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var ReportService
     */
    protected $reportService;

    /**
     * ReportExtension constructor.
     * @param EntityManagerInterface $em
     * @param ReportService $reportService
     */
    public function __construct(EntityManagerInterface $em, ReportService $reportService)
    {
        $this->em = $em;
        $this->reportService = $reportService;
    }

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('get_reports_collected_data', [$this, 'getReportsCollectedData']),
            new \Twig_SimpleFunction('get_report', [$this, 'getReport']),
            new \Twig_SimpleFunction('get_not_approved_date', [$this, 'getNotApprovedDate']),
        ];
    }

    /**
     * @param string $ids
     * @return array
     */
    public function getReportsCollectedData(string $ids): array
    {
        $data = [];
        $reportData = explode(',', $ids);
        $cashierReports = $backofficerReports = $migrationReports =[];

        if ($reportData) {
            foreach ($reportData as $report) {
                $tmp = explode('-', $report);
                if ($tmp[1] == Report::REPORT_TYPE_CASHIER) {
                    $cashierReports[] = $tmp[0];
                }

                if ($tmp[1] == Report::REPORT_TYPE_BACKOFFICER) {
                    $backofficerReports[] = $tmp[0];
                    $reportApproved = $this->getReport($tmp[0]);
                }

                if ($tmp[1] == Report::REPORT_TYPE_MIGRATION) {
                    $migrationReports[] = $tmp[0];
                    $data['reportTypeMigration'] = true;
                    $reportApproved = $this->getReport($tmp[0]);
                }
            }

            $data['cashierReports'] = $cashierReports;
            $data['backofficerReports'] = $backofficerReports;
            $data['approved'] = isset($reportApproved) ? $reportApproved->getApproved() : false;

            $reportPositionValueRepository = $this->em->getRepository(ReportPositionValue::class);

            if ($cashierReports) {
                $data['amountData'] = $reportPositionValueRepository->getReportTotalAmount(array_values(array_merge($cashierReports, $backofficerReports)), 'totalSales');
            }

            if (count($backofficerReports) == count($reportData) && empty($cashierReports)) {
                $data['amountData'] = $reportPositionValueRepository->getReportTotalAmount(array_values($backofficerReports), 'salesCategory');
            }

            if ($migrationReports) {
                $data['amountData'] = $reportPositionValueRepository->getReportTotalAmount(array_values($migrationReports), 'totalSales');
            }
        }

        return $data;
    }

    /**
     * @param int $id
     * @return null|object
     */
    public function getReport(int $id): ?Report
    {
        $report = $this->em->getRepository(Report::class)->find($id);

        if (!$report) {
            throw new NotFoundHttpException('Report not found.');
        }

        return $report;
    }

    /**
     * @param Facility $facility
     * @param int $daysInPast
     * @return string
     * @throws \Exception
     */
    public function getNotApprovedDate(Facility $facility, int $daysInPast): string
    {
        $format = 'd.m.Y';
        $date = (new \DateTimeImmutable('now'))->format($format);

        $dates = $this->reportService->getDisabledDates($facility);
        rsort($dates);
        $this->reportService->getFreeReportDate($dates, $format, $date, $daysInPast);

        return $date;
    }
}
