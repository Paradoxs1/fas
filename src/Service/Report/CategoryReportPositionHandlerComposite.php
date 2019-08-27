<?php

namespace App\Service\Report;

use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Service\Report\Handler\CategoryReportPositionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

class CategoryReportPositionHandlerComposite implements CategoryReportPositionHandlerInterface
{
    /**
     * @var array
     */
    private $handlers;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * ReportPositionHandlerComposite constructor.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->handlers = [];
    }

    /**
     * @param CategoryReportPositionHandlerInterface $handler
     */
    public function addHandler(CategoryReportPositionHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @return bool
     */
    public function checkChanges(Report $report, array $requestData): bool
    {
        $handlers = $this->getHandlers();
        if ($handlers) {
            /** @var CategoryReportPositionHandlerInterface $handler */
            foreach ($handlers as $handler) {
                if ($handler->checkChanges($report, $requestData)) {
                    return true;
                }
            }
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
        $handlers = $this->getHandlers();
        if ($handlers) {
            /** @var CategoryReportPositionHandlerInterface $handler */
            foreach ($handlers as $handler) {
                $handler->applyChanges($newReport, $oldReport, $requestData);
            }
        }
    }

    /**
     * @param string $amount
     * @return float
     */
    public static function formatAmount(string $amount)
    {
        return floatval(preg_replace('/[^\d\.]/', '', $amount));
    }
}
