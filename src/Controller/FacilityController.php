<?php

namespace App\Controller;

use App\Repository\ReportRepository;
use App\Service\Facility\ConfigurationParamsHandlerComposite;
use App\Service\Facility\FacilityConfigurationProcessor;
use App\Service\Manager\FacilityManager;
use App\Service\ReportService;
use App\Service\Routine\DefaultRoutine;
use App\Service\Routine\RoutineRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\Facility;
use App\Form\FacilityLayoutType;
use App\Service\FacilityService;
use App\Service\UserService;
use App\Service\ConfigurationService;

/**
 * Class FacilityController
 * @package App\Controller
 */
class FacilityController extends AbstractController
{
    /**
     * @Route("/tenant/facilities", name="tenant_facility")
     * @param Request $request
     * @param FacilityManager $facilityManager
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function index(Request $request, FacilityManager $facilityManager): Response
    {
        return $facilityManager->list(null, $request->query->get('page', 1));
    }

    /**
     * @Route("/tenant/facilities/add", name="tenant_facility_new")
     * @param Request $request
     * @param FacilityManager $facilityManager
     * @return RedirectResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function create(Request $request, FacilityManager $facilityManager): Response
    {
        return $facilityManager->create($request);
    }

    /**
     * @Route("/tenant/facilities/{facilityId}/edit", name="tenant_facility_edit", methods="GET|POST")
     * @param Request $request
     * @param FacilityManager $facilityManager
     * @return RedirectResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function edit(Request $request, FacilityManager $facilityManager): Response
    {
        return $facilityManager->edit($request);
    }

    /**
     * @Route("/tenant/facilities/{id}/statistics", name="facility_statistics")
     * @param Facility $facility
     * @param FacilityService $facilityService
     * @param UserService $userService
     * @return RedirectResponse|Response
     */
    public function statistics(Facility $facility, FacilityService $facilityService, UserService $userService): Response
    {
        //TODO: check ?!
        if (!$facilityService->hasRoleInFacility($this->getUser(), $facility, 'ROLE_FACILITY_STAKEHOLDER')) {
            $facility = $userService->getFirstStakeholderFacility($this->getUser());

            if ($facility) {
                return $this->redirectToRoute('facility_statistics', [
                    'id' => $facility->getId()
                ]);
            }
            else {
                return $this->redirectToRoute('login', []);
            }
        }

        return $this->render('facility/statistics.html.twig', [
            'facility' => $facility
        ]);
    }

    /**
     * @Route("/tenant/facilities/{id}/configuration", name="facility_configuration")
     * @param Request $request
     * @param Facility $facility
     * @param ConfigurationService $configurationService
     * @param ConfigurationParamsHandlerComposite $configurationParamsHandlerComposite
     * @param FacilityConfigurationProcessor $facilityConfigurationProcessor
     * @param RoutineRegistry $routineRegistry
     * @return Response
     * @throws \Exception
     */
    public function configuration(
        Request $request,
        Facility $facility,
        ConfigurationService $configurationService,
        ConfigurationParamsHandlerComposite $configurationParamsHandlerComposite,
        FacilityConfigurationProcessor $facilityConfigurationProcessor,
        RoutineRegistry $routineRegistry
    ): Response {
        $tenant = $this->getUser()->getTenant();

        if (!$tenant || $facility->getTenant()->getId() != $tenant->getId()) {
            throw new AccessDeniedHttpException();
        }

        $facilityLayout = $facility->getFacilityLayouts()->last();

        if (!$facilityLayout) {
            throw new NotFoundHttpException('Default template for current tenant not found.');
        }

        $isDefaultRoutine = false;
        if ($facility->getRoutine()->getName() == DefaultRoutine::NAME)
        {
            $isDefaultRoutine = true;
        }
        $form = $this->createForm(FacilityLayoutType::class, $facilityLayout, ['isDefaultRoutine' => $isDefaultRoutine]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestData = $request->request->all();
            $facilityConfigurationProcessor->process($requestData, $facilityLayout);
        }

        $facilityLayout = $facility->getFacilityLayouts()->last();

        $data = [];
        $data = $configurationParamsHandlerComposite->getPositions($data, $facilityLayout);
        $facilityRoutine = $facility->getRoutine();

        if (!$facilityRoutine) {
            throw new \InvalidArgumentException("Routine doesn't set for this facility");
        }

        return $this->render('facility/configuration.html.twig', [
            'data'                   => $data,
            'facility'               => $facility,
            'routine'                => $routineRegistry->getRoutine($facilityRoutine->getName()),
            'form'                   => $form->createView(),
            'daysOfWeekMapping'      => $configurationService->getDaysOfWeekMapping(),
            'cashierEditAllowedDays' => $this->getParameter('cashier_edit_allowed_days'),
            'facilityLayout'         => $facilityLayout,
        ]);
    }

    /**
     * @Route("/tenant/facilities/{id}/reports", name="facility_reports")
     * @param Request $request
     * @param FacilityManager $facilityManager
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function reports(Request $request, FacilityManager $facilityManager): Response
    {
        return $facilityManager->listReports($request->get('id'), $request->query->get('page', 1), ['approved' => $request->query->get('approved')]);
    }

    /**
     * @Route("/tenant/facilities/{id}/dashboard", name="facility_dashboard")
     * @param Facility $facility
     * @param ReportRepository $reportRepository
     * @param ReportService $reportService
     * @return Response
     */
    public function dashboard(Facility $facility, ReportRepository $reportRepository, ReportService $reportService): Response
    {
        $tenant = $this->getUser()->getTenant();

        if (!$tenant || $facility->getTenant()->getId() != $tenant->getId()) {
            throw new AccessDeniedHttpException();
        }

        $totalSalesData = $reportRepository->getReportsByFacilityBetweenStatementDates($facility);

        return $this->render('facility/dashboard.html.twig', [
            'facility' => $facility,
            'chartData' => $reportService->transformDataForChart($totalSalesData),
        ]);
    }

    /**
     * @Route("/tenant/facilities/{id}/delete", name="facility_delete", methods="DELETE")
     * @param Request $request
     * @param FacilityManager $facilityManager
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function delete(Request $request, FacilityManager $facilityManager): JsonResponse
    {
        return $facilityManager->delete($request);
    }
}
