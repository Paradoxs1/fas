<?php

namespace App\Controller;

use App\Service\Manager\FacilityAccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FacilityAccountController
 * @package App\Controller
 */
class FacilityAccountController extends AbstractController
{
    /**
     * @Route("/tenant/facilities/{id}/cashiers", name="facility_accounts")
     * @param Request $request
     * @param FacilityAccountManager $facilityAccountManager
     * @return Response
     */
    public function index(Request $request, FacilityAccountManager $facilityAccountManager)
    {
        return $facilityAccountManager->list($request->get('id'), $request->query->get('page', 1));
    }

    /**
     * @Route("/tenant/facilities/{id}/cashiers/add", name="facility_account_add", methods="GET|POST")
     * @param Request $request
     * @param FacilityAccountManager $facilityAccountManager
     * @return Response
     */
    public function create(Request $request, FacilityAccountManager $facilityAccountManager): Response
    {
        return $facilityAccountManager->create($request);
    }

    /**
     * @Route("/tenant/facilities/{id}/cashiers/{userId}/edit", name="facility_account_edit", methods="GET|POST")
     * @param Request $request
     * @param FacilityAccountManager $facilityAccountManager
     * @return Response
     */
    public function edit(Request $request, FacilityAccountManager $facilityAccountManager): Response
    {
        return $facilityAccountManager->edit($request);
    }
}
