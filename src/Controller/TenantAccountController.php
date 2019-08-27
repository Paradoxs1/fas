<?php

namespace App\Controller;

use App\Service\Manager\TenantAccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TenantAccountController
 * @package App\Controller
 */
class TenantAccountController extends AbstractController
{
    /**
     * @Route("/tenant/users", name="tenant_accounts")
     * @param Request $request
     * @param TenantAccountManager $tenantAccountManager
     * @return Response
     */
    public function index(Request $request, TenantAccountManager $tenantAccountManager): Response
    {
        return $tenantAccountManager->list(null, $request->query->get('page', 1));
    }

    /**
     * @Route("tenant/users/{id}/edit", name="tenant_account_edit", methods="GET|POST")
     * @param Request $request
     * @param TenantAccountManager $tenantAccountManager
     * @return Response
     */
    public function edit(int $id, Request $request, TenantAccountManager $tenantAccountManager): Response
    {
        return $tenantAccountManager->edit($request);
    }

    /**
     * @Route("tenant/users/add", name="tenant_account_new", methods="GET|POST")
     * @param Request $request
     * @param TenantAccountManager $tenantAccountManager
     * @return Response
     */
    public function create(Request $request, TenantAccountManager $tenantAccountManager): Response
    {
        return $tenantAccountManager->create($request);
    }
}
