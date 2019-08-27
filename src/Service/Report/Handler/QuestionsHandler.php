<?php

namespace App\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\AccountingPosition;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Entity\Role;
use App\Repository\AccountingPositionRepository;
use App\Repository\FlexParamRepository;
use App\Service\FacilityService;
use App\Service\FlexParamService;
use App\Service\ReportPositionService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class QuestionsHandler implements CategoryReportPositionHandlerInterface
{
    /**
     * @var FacilityService
     */
    private $facilityService;

    /**
     * @var FlexParamService
     */
    private $flexParamService;

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
     * @var ReportPositionService
     */
    private $reportPositionService;

    /**
     * QuestionsHandler constructor.
     * @param FacilityService $facilityService
     * @param FlexParamService $flexParamService
     * @param EntityManagerInterface $em
     * @param ReportService $reportService
     * @param TokenStorageInterface $tokenStorage
     * @param ReportPositionService $reportPositionService
     */
    public function __construct(
        FacilityService $facilityService,
        FlexParamService $flexParamService,
        EntityManagerInterface $em,
        ReportService $reportService,
        TokenStorageInterface $tokenStorage,
        ReportPositionService $reportPositionService
    )
    {
        $this->facilityService = $facilityService;
        $this->flexParamService = $flexParamService;
        $this->em = $em;
        $this->reportService = $reportService;
        $this->tokenStorage = $tokenStorage;
        $this->reportPositionService = $reportPositionService;
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @return bool
     */
    public function checkChanges(Report $report, array $requestData): bool
    {
        $showQuestions = $this->facilityService->hasRoleInFacility($report->getCreatedBy(), $report->getFacilityLayout()->getFacility(), Role::ROLE_FACILITY_MANAGER) ? true : false;

        if ($showQuestions) {
            $questions = $this->flexParamService->getQuestions($report->getFacilityLayout(), $report, true);
            foreach ($questions as $reportPositionId => $question) {
                if (isset($requestData['question-answer'][$reportPositionId])) {
                    if (!isset($question['answer']) && $requestData['question-answer'][$reportPositionId]) {
                        return true;
                    }
                    if (isset($question['answer']) && $requestData['question-answer'][$reportPositionId] != $question['answer']) {
                        return true;
                    }
                } else {
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
     * @throws \Exception
     */
    public function applyChanges(Report $newReport, Report $oldReport, array $requestData): void
    {
        $account = $this->tokenStorage->getToken()->getUser();

        if ($this->checkChanges($oldReport, $requestData)) {
            $values = $this->em->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($oldReport, FlexParamService::ACCOUNTING_CATEGORY_QUESTION_KEY);

            if ($values) {
                /** @var ReportPositionValue $value */
                foreach ($values as $value) {
                    $accountingPositionId = $value->getReportPosition()->getAccountingPosition()->getId();

                    if ($requestData['question-answer'][$accountingPositionId] != $value->getValue()) {
                        $value->setModifiedBy($account);
                        $value->setValue($requestData['question-answer'][$accountingPositionId]);
                        $value->getReportPosition()->setModifiedBy($account);
                        $value->getReportPosition()->setModifiedAt(new \DateTimeImmutable());
                        if ($value->getParentReportPositionValue() !== null) {
                            $value->setParentReportPositionValue($value->getParentReportPositionValue());
                        } else {
                            $value->setParentReportPositionValue($value);
                        }
                    }
                    unset($requestData['question-answer'][$accountingPositionId]);
                }
            } else {
                if ($requestData['question-answer']) {
                    $this->reportService->addQuestionAnswers($account, $newReport, $requestData['question-answer']);
                }
            }

            if (isset($requestData['question-answer']) && count($requestData['question-answer']) > 0) {
                $this->addPositions($requestData['question-answer'], $newReport, $account);
            }
        }
    }

    /**
     * @param array $questionsAnswersData
     * @param Report $report
     * @param Account $account.
     *
     */
    public function addPositions(array $questionsAnswersData, Report $report, Account $account)
    {
        if (count($questionsAnswersData) > 0) {

            /** @var FlexParamRepository $flexparamRepository */
            $flexparamRepository = $this->em->getRepository(FlexParam::class);
            /** @var AccountingPositionRepository $accountingPositionRepository */
            $accountingPositionRepository = $this->em->getRepository(AccountingPosition::class);

            foreach ($questionsAnswersData as $accountingPositionId => $answer) {
                if (!empty($answer)) {
                    $accountingPosition = $accountingPositionRepository->find($accountingPositionId);
                    $answerFlexParam = $flexparamRepository->findOneByAccountingPositionAndType($accountingPosition,'answer');

                    $reportPosition = new ReportPosition();
                    $reportPosition->setAccountingPosition($accountingPosition);
                    $reportPosition->setModifiedBy($account);

                    $reportPositionValueAnswer = new ReportPositionValue();
                    $reportPositionValueAnswer->setValue($answer);
                    $reportPositionValueAnswer->setParameter($answerFlexParam);
                    $reportPositionValueAnswer->setModifiedBy($account);
                    $reportPositionValueAnswer->setSequence(1);

                    $reportPosition->addReportPositionValue($reportPositionValueAnswer);
                    $report->addReportPosition($reportPosition);
                }
            }
        }
    }
}
