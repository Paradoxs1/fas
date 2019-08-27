<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\Role;
use App\Repository\ReportRepository;
use App\Service\MoneyService;
use App\Service\Report\CategoryReportPositionHandlerComposite;
use App\Service\Report\Handler\EditReportProcessor;
use App\Service\Report\ReopenReportService;
use App\Service\Report\ReportsOverviewProcessor;
use App\Service\Routine\RoutineRegistry;
use App\Service\UserService;
use App\Service\ReportService;
use App\Service\FacilityService;
use App\Service\FlexParamService;
use App\Service\TranslationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ReportController
 * @package App\Controller
 */
class ReportController extends AbstractController
{
    /**
     * Cashier report (Facility User, Facility Manager roles)
     *
     * @Route("/report/{date}", name="cashier_report")
     * @param Request $request
     * @param ReportService $reportService
     * @param UserService $userService
     * @param FacilityService $facilityService
     * @param FlexParamService $flexParamService
     * @param TranslationService $translationService
     * @param SessionInterface $session
     * @param string $date
     * @return JsonResponse|RedirectResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function cashier(
        Request $request,
        ReportService $reportService,
        UserService $userService,
        FacilityService $facilityService,
        FlexParamService $flexParamService,
        TranslationService $translationService,
        SessionInterface $session,
        string $date = ''
    ): Response {
        $user = $this->getUser();
        $facility = $userService->getFacilityUserFacilityManagerFacility($user);

        if (!$facility) {
            return $this->redirectToRoute('login', []);
        }

        $facilityLayout = $facility->getFacilityLayouts()->last();

        //Allowed days in past to make a report
        $daysInPast = $reportService->getDaysInPast($facilityLayout);

        //Dates, disabled by several conditions
        $datesDisabled = $reportService->getDisabledDatesForCashier($facility, $user);

        if (!$reportService->dateSelectedValid($date)) {
            $date = $reportService->getCurrentDate();
            $reportService->getFreeReportDate($datesDisabled, 'd.m.Y', $date, $daysInPast, true);

            return $this->redirectToRoute('cashier_report', [
                'date' => $date
            ]);
        }

        $total = $session->get('total');

        if (!$reportService->isDateAllowed($facility, $user, $date, $datesDisabled)) {
            if ($total) {
                $session->remove('total');

                return $this->render('report/report-successfully-created.html.twig', [
                    'facility' => $facility,
                    'total'    => $total
                ]);
            }

            return $this->render('report/report-period-closed.html.twig', [
                'facility' => $facility
            ]);
        }

        $requestData = $request->request->all();

        $report = $date ? $reportService->getReportByFacilityUserStatementDate($facility, $user, $date, $datesDisabled) : null;

        #Number of hours which should pass after midnight so we assume the next day started.
        #For instance: if set to 3 that means that the next day starts after 3 A.M.
        $numberOfHoursToShift = $this->getParameter('number_of_hours_to_shift');

        //Translations for JS
        $translationsForJS = $translationService->getTranslationsForReportJS();

        //Show questions for ROLE_FACILITY_MANAGER only
        $showQuestions = $facilityService->hasRoleInFacility($user, $facility, 'ROLE_FACILITY_MANAGER') ? true : false;

        //Questions
        $questions = $showQuestions ? $flexParamService->getQuestions($facilityLayout, $report, true) : [];

        //Comment
        $comment = $report ? $reportService->getComment($facilityLayout, $report) : null;

        //Total Sales
        $totalSales = $report ? $reportService->getTotalSales($facilityLayout, $report) : null;

        //Tips (Dues)
        $tips = $reportService->getTips($facilityLayout, $report);

        if ($request->isMethod('POST') && $reportService->dataValid($facilityLayout, $requestData)) {
            try {
                $reportService->saveReport($facilityLayout, $user, $requestData, false);
            } catch (\Exception $exception) {
                throw new ServiceUnavailableHttpException('Service unavailable');
            }

            $session->set('total', $requestData['total-dues']);
            return new JsonResponse(['result' => 'success']);
        }

        $paymentMethods = $reportService->getPaymentMethods($facilityLayout, $report ?: null);
        $allVouchersSum = $reportService->getAllVouchersSum($paymentMethods);

        return $this->render('report/cashier.html.twig', [
            'paymentMethods'       => $paymentMethods,
            'facility'             => $facility,
            'daysInPast'           => $daysInPast,
            'facilityLayout'       => $facilityLayout,
            'numberOfHoursToShift' => $numberOfHoursToShift,
            'questions'            => $questions,
            'datesDisabled'        => json_encode($datesDisabled),
            'currency'             => $facilityLayout->getCurrency()->getAdministrativeName(),
            'translationsForJS'    => $translationsForJS,
            'date'                 => $date,
            'report'               => $report,
            'comment'              => $comment,
            'totalSales'           => $totalSales,
            'allVouchersSum'       => $allVouchersSum,
            'tips'                 => $tips,
        ]);
    }

    /**
     * Backofficer report (Tenant user role)
     *
     * @Route("/tenant/facilities/{id}/reports/add/{date}", name="backofficer_report")
     * @param Facility $facility
     * @param Request $request
     * @param ReportService $reportService
     * @param FacilityService $facilityService
     * @param TranslationService $translationService
     * @param CategoryReportPositionHandlerComposite $reportPositionHandlerComposite
     * @param EditReportProcessor $editReportProcessor
     * @param RoutineRegistry $routineRegistry
     * @param ReportRepository $reportRepository
     * @param string $date
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function backofficer(
        Facility $facility,
        Request $request,
        ReportService $reportService,
        FacilityService $facilityService,
        TranslationService $translationService,
        CategoryReportPositionHandlerComposite $reportPositionHandlerComposite,
        EditReportProcessor $editReportProcessor,
        RoutineRegistry $routineRegistry,
        ReportRepository $reportRepository,
        string $date
    ): Response {
        if (!$facility) {
            return $this->redirectToRoute('login', []);
        }

        $user = $this->getUser();

        if (!$facilityService->hasRoleInFacility($user, $facility, 'ROLE_TENANT_USER')) {
            return $this->redirectToRoute('login', []);
        }

        //Dates, disabled by several conditions
        $datesDisabled = $reportService->getDisabledDates($facility);

        //Current date report
        $report = $date ? $reportService->getReportByFacilityUserStatementDate($facility, $user, $date, $datesDisabled) : null;

        $datesDisabled = $reportService->transformFormatDates($datesDisabled, 'd.m.Y');

        //Actual Facility Layout
        $facilityLayout = $reportService->getFacilityLayoutForReport($facility, $report);

        //Date which is valid(no report yet) for redirection
        if ($report && $report->getCreatedBy()->getId() != $user->getId()) {
            $availableDateToRedirect = $reportService->getAvailableDateToRedirect($facilityLayout, $datesDisabled);

            if (!$availableDateToRedirect) {
                return $this->redirectToRoute('facility_reports', [
                    'id' => $facility->getId(),
                ]);
            } else {
                return $this->redirectToRoute('backofficer_report', [
                    'id'   => $facility->getId(),
                    'date' => $availableDateToRedirect
                ]);
            }
        }

        $requestData = $request->request->all();

        //Allowed days in past to make a report
        $daysInPast = $facilityService->hasRoleInFacility($user, $facility, 'ROLE_TENANT_USER') ? null : $reportService->getDaysInPast($facilityLayout);

        #Number of hours which should pass after midnight so we assume the next day started.
        #For instance: if set to 3 that means that the next day starts after 3 A.M.
        $numberOfHoursToShift = $this->getParameter('number_of_hours_to_shift');

        //Translations for JS
        $translationsForJS = $translationService->getTranslationsForReportJS();

        $routine = $routineRegistry->getRoutine($facility->getRoutine()->getName());
        $overlayTemplate = $routine->getOverlayTemplate();
        $number = $newReport = null;

        if ($request->isMethod('POST') && $reportService->dataValid($facilityLayout, $requestData, $user)) {
            $lastReport = $reportRepository->getLatestApprovedReport($facility);
            if ($overlayTemplate != null) {
                $number = ($lastReport && $lastReport[0]->getNumber()) ? $lastReport[0]->getNumber() + 1 : 1;
            }

            if ($report && $report->getApproved() != true) {
                if ($reportPositionHandlerComposite->checkChanges($report, $requestData)) {
                    $editReportProcessor->process($report, $requestData, true, $number);
                } else {
                    $reportService->approveReport($report, $number);
                }
            } else {
                $reportService->saveReport($facilityLayout, $user, $requestData, true, $number);
            }

            if (is_null($overlayTemplate)) {
                return new JsonResponse(['result' => 'success']);
            }
            if ($routine->getBackofficerProcessor()) {
                $newReport = $routine->getBackofficerProcessor()->saveOverlayData($facility, $date, $requestData);
            }
        }

        $report = $newReport ?? $report;
        $comment = $report ? $reportService->getComment($facilityLayout, $report) : null;
        $sales = $reportService->getSales($facilityLayout, $report);
        $tips = $reportService->getTips($facilityLayout, $report);
        $cash = $reportService->getCash($facilityLayout, $report);

        $paymentMethods = $reportService->getPaymentMethods($facilityLayout, $report);
        $allVouchersSum = $reportService->getAllVouchersSum($paymentMethods);

        return $this->render('report/backofficer.html.twig', [
            'paymentMethods'       => $paymentMethods,
            'facility'             => $facility,
            'daysInPast'           => $daysInPast,
            'facilityLayout'       => $facilityLayout,
            'numberOfHoursToShift' => $numberOfHoursToShift,
            'datesDisabled'        => json_encode($datesDisabled),
            'currency'             => $facilityLayout->getCurrency()->getAdministrativeName(),
            'translationsForJS'    => $translationsForJS,
            'date'                 => $date,
            'report'               => $report,
            'comment'              => $comment,
            'sales'                => $sales,
            'salesSum'             => $reportService->getSalesSum($report, $sales),
            'allVouchersSum'       => $allVouchersSum,
            'tips'                 => $tips,
            'cash'                 => $cash,
            'overlayTemplate'      => $overlayTemplate
        ]);
    }

    /**
     * @param Request $request
     * @param ReportService $reportService
     * @return JsonResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @Route("/tenant/facilities/{facilityId}/reports/{checkDate}/check", name="check_report")
     */
    public function checkReports(Request $request, ReportService $reportService): Response
    {
        /** @var Account $loggedUser */
        $loggedUser =  $this->getUser();
        $facilityId = $request->get('facilityId');
        $checkDate = $request->get('checkDate');

        /** @var Facility $facility */
        $facility = $this->getDoctrine()->getRepository(Facility::class)->find($facilityId);

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found');
        }

        $tenant = $facility->getTenant();

        if (!$tenant) {
            throw new NotFoundHttpException('Tenant not found');
        }

        if ($facility->getTenant()->getId() != $tenant->getId() || $loggedUser->getTenant()->getId() != $tenant->getId()) {
            throw new AccessDeniedHttpException();
        }

        $dt = \DateTime::createFromFormat("Y-m-d", $checkDate);
        if ($dt == false) {
            throw new InvalidParameterException('Invalid date');
        }

        $date = $dt->format('Y-m-d'). ' 00:00:00';
        /** @var Report $report */
        $cashierReport = $this->getDoctrine()->getRepository(Report::class)->findOneByFacilityStatementDate($facility, $date);

        if (!$cashierReport) {
            throw new NotFoundHttpException('No cashier reports found');
        }

        $facilityLayout = $cashierReport->getFacilityLayout();
        /** @var Report $backofficerReport */
        $backofficerReport = $this->getDoctrine()->getRepository(Report::class)->findOneByFacilityUserStatementDate($facility, null, $date, Report::REPORT_TYPE_BACKOFFICER);

        if ($backofficerReport && $backofficerReport->getApproved() == true) {
            throw new NotFoundHttpException('No cashier reports found');
        }

        $requestData = $request->request->all();

        $sales = $reportService->getSales($cashierReport->getFacilityLayout(), $backofficerReport);
        $creditCards = $reportService->getCreditCards($facilityLayout, $backofficerReport);
        $cash = $reportService->getCashFields($facilityLayout);

        if ($request->isMethod('POST')) {
            if ($backofficerReport) {
                foreach ($backofficerReport->getReportPositions() as $reportPosition) {
                    foreach ($reportPosition->getReportPositionValues() as $value) {
                        $value->setDeletedAt(new \DateTimeImmutable());
                        $this->getDoctrine()->getManager()->persist($value);
                    }
                    $reportPosition->setDeletedAt(new \DateTimeImmutable());
                    $this->getDoctrine()->getManager()->persist($reportPosition);
                }
                $this->getDoctrine()->getManager()->flush();
                $reportService->saveReportParams($facilityLayout, $loggedUser, $requestData, $backofficerReport);
            } else {
                $reportService->saveReport($facilityLayout, $loggedUser, $requestData);
            }

            return new JsonResponse([
                'result' => 'success',
                'path' => $this->generateUrl('report_overview', ['checkDate' => $checkDate, 'facilityId' => $facilityId])
            ]);
        }
        $reports = $this->getDoctrine()->getRepository(Report::class)->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_CASHIER);
        $cashiers = $cashiersAmounts = [];

        if ($backofficerReport) {
            $cashFromCashier = $reportService->getReceivedCash($backofficerReport);

            if (!empty($cashFromCashier)) {
                foreach ($cashFromCashier as $reportPosition => $cashData) {
                    $cashiersAmounts[$cashData['cashier']] = $cashData['amount'];
                }
            }
        }

        foreach ($reports as $report) {
            $cashiers[$report->getCreatedBy()->getId()] = $report->getCreatedBy()->getPerson()->getFirstName() . ' ' . $report->getCreatedBy()->getPerson()->getLastName();
        }

        return $this->render('report/reports-check.html.twig', [
            'sales'       => $sales,
            'creditCards' => $creditCards,
            'cash'        => $cash,
            'cashData'    => isset($backofficerReport) ? $reportService->getCashData($facilityLayout, $backofficerReport) : null,
            'currency'    => $facilityLayout->getCurrency()->getAdministrativeName(),
            'reports'     => $reports,
            'current_facility' => $facility,
            'cashiers' => $cashiers,
            'backofficerReport' => $backofficerReport,
            'cashiersAmounts' => $cashiersAmounts,
            'facilityLayout' => $facilityLayout,
        ]);
    }

    /**
     * @param Request $request
     * @param ReportService $reportService
     * @param ReportsOverviewProcessor $reportsProcessor
     * @param RoutineRegistry $routineRegistry
     * @return RedirectResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @Route("/tenant/facilities/{facilityId}/reports/{checkDate}/overview", name="report_overview")
     */
    public function reportsOverview(Request $request, ReportService $reportService, ReportsOverviewProcessor $reportsProcessor, RoutineRegistry $routineRegistry): Response
    {
        /** @var Account $loggedUser */
        $loggedUser =  $this->getUser();
        $facilityId = $request->get('facilityId');
        $checkDate = $request->get('checkDate');
        $approved = $request->get('approved') ? $request->get('approved') : false;

        $reportRepository = $this->getDoctrine()->getRepository(Report::class);

        /** @var Facility $facility */
        $facility = $this->getDoctrine()->getRepository(Facility::class)->find($facilityId);

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found');
        }

        $tenant = $facility->getTenant();

        if (!$tenant) {
            throw new NotFoundHttpException('Tenant not found');
        }

        if ($facility->getTenant()->getId() != $tenant->getId() || $loggedUser->getTenant()->getId() != $tenant->getId()) {
            throw new AccessDeniedHttpException();
        }

        $dt = \DateTime::createFromFormat("Y-m-d", $checkDate);
        if ($dt == false) {
            throw new InvalidParameterException('Invalid date');
        }

        $date = $dt->format('Y-m-d'). ' 00:00:00';
        // check allow this route
        $reports = $reportRepository->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_BACKOFFICER, $approved);

        if (!$reports) {
            throw new NotFoundHttpException('Reports not found');
        }

        /** @var Report $report */
        $report = $reports[0];
        $facilityLayout = $report->getFacilityLayout();
        $requestData = $request->request->all();

        $sales = $reportService->getSales($report->getFacilityLayout(), $report);
        $creditCards = $reportService->getCreditCards($facilityLayout, $report);
        $cashiersReports = $this->getDoctrine()->getRepository(Report::class)->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_CASHIER);
        $cash = $cashiersReports ? $reportService->getCashData($facilityLayout, $report) : [];
        $questionsAnswers = $reportService->getQuestionsAnswers($facilityLayout, $cashiersReports);

        $data = $reportsProcessor->process($facility, $date, $approved);

        $routine = $routineRegistry->getRoutine($facility->getRoutine()->getName());
        $overlayData = null;
        if ($routine->getCashierProcessor()) {
            $overlayData = $routine->getCashierProcessor()->getOverlayData($facility, $date, [], $approved);
        }
        $overlayTemplate = $routine->getOverlayTemplate();

        if ($request->isMethod('POST')) {
            $reportsProcessor->finalize($report, $requestData);
            return $this->redirectToRoute('facility_reports', ['id' => $facility->getId()]);
        }

        return $this->render('report/reports-overview.html.twig', [
            'facilityId'       => $facilityId,
            'checkDate'        => $dt,
            'sales'            => $sales,
            'creditCards'      => $creditCards,
            'cash'             => $cash,
            'currency'         => $facilityLayout->getCurrency()->getAdministrativeName(),
            'questionsAnswers' => $questionsAnswers,
            'current_facility' => $facility,
            'data'             => $data,
            'approved'         => $approved,
            'overlayData'      => $overlayData,
            'overlayTemplate'  => $overlayTemplate,
            'facility'         => $facility,
            'params'           => json_decode($facility->getRoutine()->getParams()),
        ]);
    }

    /**
     * @param Facility $facility
     * @param Request $request
     * @param ReportService $reportService
     * @param FlexParamService $flexParamService
     * @param FacilityService $facilityService
     * @param CategoryReportPositionHandlerComposite $reportPositionHandlerComposite
     * @param EditReportProcessor $editReportProcessor
     * @param UrlGeneratorInterface $router
     * @return JsonResponse|Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @Route("/tenant/facilities/{id}/reports/{date}/edit/{reportId}", name="edit_cashier_report")
     */
    public function editCashierReport(
        Facility $facility,
        Request $request,
        ReportService $reportService,
        FlexParamService $flexParamService,
        FacilityService $facilityService,
        CategoryReportPositionHandlerComposite $reportPositionHandlerComposite,
        EditReportProcessor $editReportProcessor,
        UrlGeneratorInterface $router
    ): Response {
        $loggedUser = $this->getUser();
        $date = $request->get('date');
        $facilityId = $request->get('id');
        $reportId = $request->get('reportId');

        if ($loggedUser->getTenant()->getId() != $facility->getTenant()->getId()) {
            throw new AccessDeniedHttpException();
        }

        $dt = \DateTime::createFromFormat("Y-m-d", $date);
        if (!$dt) {
            throw new InvalidParameterException('Invalid date');
        }

        $date = $dt->format('Y-m-d'). ' 00:00:00';

        /** @var Report $report */
        $report = $this->getDoctrine()->getRepository(Report::class)->findOneByIdAndStatementDate($reportId, $date);

        if (!$report) {
            throw new NotFoundHttpException('Report not found');
        }

        if ($report && $report->getDeletedAt() !== null) {
            throw new NotFoundHttpException('Report not found');
        }

        if ($report->getFacilityLayout()->getFacility()->getId() != $facilityId) {
            throw new NotFoundHttpException('Facility not found');
        }

        /** @var Report $backofficerReport */
        $backofficerReport = $this->getDoctrine()->getRepository(Report::class)->findOneByFacilityStatementDate($facility, $date, Report::REPORT_TYPE_BACKOFFICER, false);

        if ($backofficerReport && $backofficerReport->getApproved() == true) {
            throw new NotFoundHttpException('No cashier reports found');
        }

        $requestData = $request->request->all();
        $paymentMethods = $reportService->getPaymentMethods($report->getFacilityLayout(), $report);
        $totalSales = $reportService->getTotalSales($report->getFacilityLayout(), $report);
        $allVouchersSum = $reportService->getAllVouchersSum($paymentMethods);
        $tips = $reportService->getTips($report->getFacilityLayout(), $report);
        $comment = $reportService->getComment($report->getFacilityLayout(), $report);
        $showQuestions = $facilityService->hasRoleInFacility($report->getCreatedBy(), $facility, Role::ROLE_FACILITY_MANAGER);
        $questions = $showQuestions ? $flexParamService->getQuestions($report->getFacilityLayout(), $report, true) : [];
        $numberOfHoursToShift = $this->getParameter('number_of_hours_to_shift');
        $datesDisabled = $reportService->getDisabledDatesForCashier($facility, $loggedUser);
        $maxReportPosition = $this->getDoctrine()->getRepository(ReportPosition::class)->getMaxReportPosition($report);

        if ($request->isMethod('POST')) {
            if ($reportPositionHandlerComposite->checkChanges($report, $requestData)) {
                $editReportProcessor->process($report, $requestData);
            }

            $data = [
                'path' => $router->generate('report_overview', [
                    'facilityId' => $report->getFacilityLayout()->getFacility()->getId(),
                    'checkDate' => $report->getStatementDate()->format('Y-m-d')
                ]),
                'result' => 'success'
            ];

            return new JsonResponse($data);
        }

        return $this->render('report/edit-cashier.html.twig', [
            'paymentMethods'       => $paymentMethods,
            'facility'             => $facility,
            'facilityLayout'       => $report->getFacilityLayout(),
            'numberOfHoursToShift' => $numberOfHoursToShift,
            'questions'            => $questions,
            'datesDisabled'        => json_encode($datesDisabled),
            'currency'             => $report->getFacilityLayout()->getCurrency()->getAdministrativeName(),
            'date'                 => $date,
            'report'               => $report,
            'comment'              => $comment,
            'totalSales'           => $totalSales,
            'allVouchersSum'       => $allVouchersSum,
            'tips'                 => $tips,
            'maxReportPosition'    => $maxReportPosition->getId()
        ]);
    }

    /**
     * Reopen backofficer report
     *
     * @Route("/tenant/facilities/{id}/reports/reopen/{date}", name="reopen_backofficer_report")
     * @param Facility $facility
     * @param string $date
     * @param TranslatorInterface $translator
     * @param ReportRepository $reportRepository
     * @param ReopenReportService $reopenReportService
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function reopenBackofficerReport(
        Facility $facility,
        TranslatorInterface $translator,
        ReportRepository $reportRepository,
        ReopenReportService $reopenReportService,
        string $date
    ): JsonResponse {
        $data = ['result' => false];

        if (!$facility || !$date) {
            $data['error'] = $translator->trans('report_reopen_error.not_data');
            return new JsonResponse($data);
        }

        $date = (\DateTime::createFromFormat("Y-m-d", $date))->format('Y-m-d') . ' 00:00:00';
        $report = $reportRepository->getReportsByFacilityAndDate($facility, $date, Report::REPORT_TYPE_BACKOFFICER, true);

        if ($report && $reopenReportService->reopenReport($report[0])) {
            $data['result'] = true;
        } else {
            $data['error'] = $translator->trans('report_reopen_error.database');
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/backofficer/facility/{id}/report/{date}/overlay", name="backofficer_overlay")
     * @Method("POST")
     * @param Facility $facility
     * @param FacilityService $facilityService
     * @param Request $request
     * @param RoutineRegistry $routineRegistry
     * @param string $date
     * @return JsonResponse
     */
    public function backofficerOverlay(Facility $facility, FacilityService $facilityService, Request $request, RoutineRegistry $routineRegistry, string $date): JsonResponse
    {
        $return = ['result' => false];
        $overlayData = null;

        if ($facility && $facilityService->hasRoleInFacility($this->getUser(), $facility, 'ROLE_TENANT_USER')) {
            $routine = $routineRegistry->getRoutine($facility->getRoutine()->getName());
            if ($routine->getBackofficerProcessor()) {
                $overlayData = $routine->getBackofficerProcessor()->getOverlayData($facility, '', $request->request->all());
            }

            $facilityLayout = $facility->getFacilityLayouts()->last();

            $return = [
                'result'  => true,
                'content' => $this->render('report/includes/overlay/rma.html.twig', [
                    'overlayData' => $overlayData,
                    'checkDate'   => $date,
                    'facility'    => $facility,
                    'currency'    => $facilityLayout ? $facilityLayout->getCurrency() : MoneyService::CHF,
                    'params'      => json_decode($facility->getRoutine()->getParams()),
                ])->getContent()
            ];
        }

        return new JsonResponse($return);
    }
}
