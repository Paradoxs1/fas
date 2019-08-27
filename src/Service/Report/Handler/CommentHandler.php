<?php

namespace App\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Entity\ReportPositionValue;
use App\Service\FlexParamService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CommentHandler implements CategoryReportPositionHandlerInterface
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
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * CommentHandler constructor.
     * @param ReportService $reportService
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ReportService $reportService, EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->reportService = $reportService;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Report $report
     * @param array $data
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkChanges(Report $report, array $data): bool
    {
        $comment = $this->reportService->getComment($report->getFacilityLayout(), $report);

        if ($comment && $comment->getValue() != $data['comment']) {
            return true;
        }

        return false;
    }

    /**
     * @param Report $newReport
     * @param Report $oldReport
     * @param array $requestData
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function applyChanges(Report $newReport, Report $oldReport, array $requestData): void
    {
        $account = $this->tokenStorage->getToken()->getUser();

        if ($this->checkChanges($oldReport, $requestData)) {
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_COMMENT_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    if ($requestData['comment'] != $value->getValue()) {
                        $value->setModifiedBy($account);
                        $value->setValue($requestData['comment']);
                        $value->getReportPosition()->setModifiedBy($account);
                        $value->getReportPosition()->setModifiedAt();
                        if ($value->getParentReportPositionValue() !== null) {
                            $value->setParentReportPositionValue($value->getParentReportPositionValue());
                        } else {
                            $value->setParentReportPositionValue($value);
                        }
                    }
                }
            } else {
                if (!empty($requestData['comment'])) {
                    $this->reportService->addComment($newReport->getFacilityLayout(), $account, $newReport, $requestData['comment']);
                }
            }
        }
    }
}
