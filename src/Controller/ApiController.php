<?php

namespace App\Controller;

use App\Entity\Facility;
use App\Service\FacilityService;
use App\Service\Routine\RoutineRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/test/facility/{id}", name="test_api_rma")
     * @Method("GET")
     * @param Request $request
     * @param FacilityService $facilityService
     * @param TranslatorInterface $translator
     * @param RoutineRegistry $routineRegistry
     * @param Facility|null $facility
     * @return JsonResponse
     */
    public function testConnectingApiRMARoutine(
        Request $request,
        FacilityService $facilityService,
        TranslatorInterface $translator,
        RoutineRegistry $routineRegistry,
        Facility $facility = null
    ): JsonResponse {
        $data = [
            'status'  => Response::HTTP_FORBIDDEN,
            'message' => $translator->trans('report_api.forbidden')
        ];

        if ($facility && $facilityService->hasRoleInFacility($this->getUser(), $facility, 'ROLE_TENANT_MANAGER')) {
            $params = $request->query->all();
            $routine = $routineRegistry->getRoutine($facility->getRoutine()->getName());

            try {
                $response = $routine->testCallApi($facility, $params['facility_layout']['params']);

                if ($response && $response->getStatus() === Response::HTTP_OK) {
                    $data = [
                        'status' => $response->getStatus(),
                        'message' => $translator->trans('report_api.test_call_success') . $response->getStatus()
                    ];
                }
            } catch (\Exception $e) {
                $code = $e->getCode() != 0 ? $e->getCode() : Response::HTTP_BAD_REQUEST;
                $data = [
                    'status' => $code,
                    'message' => $translator->trans('report_api.test_call_trouble') . $code
                ];
            }
        }

        return new JsonResponse($data);
    }
}
