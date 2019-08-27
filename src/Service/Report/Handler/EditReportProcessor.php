<?php

namespace App\Service\Report\Handler;

use App\Entity\Report;
use App\Service\Report\CategoryReportPositionHandlerComposite;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EditReportProcessor
{
    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var CategoryReportPositionHandlerComposite
     */
    private $reportPositionHandlerComposite;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * EditReportProcessor constructor.
     * @param ReportService $reportService
     * @param TokenStorageInterface $tokenStorage
     * @param CategoryReportPositionHandlerComposite $reportPositionHandlerComposite
     * @param EntityManagerInterface $em
     */
    public function __construct(
        ReportService $reportService,
        TokenStorageInterface $tokenStorage,
        CategoryReportPositionHandlerComposite $reportPositionHandlerComposite,
        EntityManagerInterface $em
    ) {
        $this->reportService = $reportService;
        $this->tokenStorage = $tokenStorage;
        $this->reportPositionHandlerComposite = $reportPositionHandlerComposite;
        $this->em = $em;
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @param bool $approved
     * @param int|null $number
     * @return void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function process(Report $report, array $requestData, bool $approved = false, ?int $number = null): void
    {
        $requestData = $this->replaceSymbolInArray($requestData, "'", '');

        $this->em->getConnection()->beginTransaction();
        try {
            $newReport = $this->reportService->addReport(
                $report->getFacilityLayout(),
                $report->getCreatedBy(),
                $report->getStatementDate(),
                isset($requestData['shifts']) ? $requestData['shifts'] : null,
                $number ?? $report->getNumber(),
                $approved,
                $report->getType()
            );

            $this->reportPositionHandlerComposite->applyChanges($newReport, $report, $requestData);
            $this->reportService->clonePositions($report, $newReport);

            $this->em->refresh($report);
            $report->setDeletedAt(new \DateTimeImmutable());

            if ($report->getParentReport() !== null) {
                $newReport->setParentReport($report->getParentReport());
            } else {
                $newReport->setParentReport($report);
            }
            $this->em->persist($newReport);
            $this->em->persist($report);
            $this->em->flush($newReport);
            $this->em->flush($report);

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $array
     * @param string $search
     * @param string $replace
     * @return array
     */
    private function replaceSymbolInArray(array $array, string $search, string $replace): array
    {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $array[$key] = $this->replaceSymbolInArray($array[$key], $search, $replace);
            } else {
                $array[$key] = str_replace($search, $replace, $array[$key]);
            }
        }

        return $array;
    }
}
