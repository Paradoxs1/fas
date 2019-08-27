<?php

namespace App\Controller;

use App\Service\Manager\StakeholderAccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StakeholderAccountController
 * @package App\Controller
 */
class StakeholderAccountController extends AbstractController
{
    /**
     * @Route("/tenant/facilities/{id}/stakeholders", name="stakeholder_accounts")
     * @param Request $request
     * @param StakeholderAccountManager $stakeholderAccountManager
     * @return Response
     */
    public function index(Request $request, StakeholderAccountManager $stakeholderAccountManager)
    {
        return $stakeholderAccountManager->list($request->get('id'), $request->query->get('page', 1));
    }

    /**
     * @Route("/tenant/facilities/{id}/stakeholders/add", name="stakeholder_account_add", methods="GET|POST")
     * @param Request $request
     * @param StakeholderAccountManager $stakeholderAccountManager
     * @return Response
     */
    public function create(Request $request, StakeholderAccountManager $stakeholderAccountManager): Response
    {
        return $stakeholderAccountManager->create($request);
    }

    /**
     * @Route("/tenant/facilities/{id}/stakeholders/{userId}/edit", name="stakeholder_account_edit", methods="GET|POST")
     * @param Request $request
     * @param StakeholderAccountManager $stakeholderAccountManager
     * @return Response
     */
    public function edit(Request $request, StakeholderAccountManager $stakeholderAccountManager): Response
    {
        return $stakeholderAccountManager->edit($request);
    }
}
