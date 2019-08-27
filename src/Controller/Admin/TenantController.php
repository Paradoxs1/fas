<?php
namespace App\Controller\Admin;

use App\Service\Manager\TenantManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TenantController
 * @package App\Controller\Admin
 */
class TenantController extends AbstractController
{
    /**
     * @Route("/admin/tenants", name="admin_tenants")
     * @param Request $request
     * @param TenantManager $tenantManager
     * @return Response
     */
    public function index(Request $request, TenantManager $tenantManager)
    {
        return $tenantManager->list(null, $request->query->get('page', 1));
    }

    /**
     * @Route("admin/tenants/{id}/edit", name="admin_tenant_edit", methods="GET|POST")
     * @param Request $request
     * @param TenantManager $tenantManager
     * @return Response
     */
    public function edit(Request $request, TenantManager $tenantManager): Response
    {
        return $tenantManager->edit($request);
    }

    /**
     * @Route("admin/tenants/add", name="admin_tenant_new", methods="GET|POST")
     * @param Request $request
     * @param TenantManager $tenantManager
     * @return Response
     */
    public function create(Request $request, TenantManager $tenantManager): Response
    {
        return $tenantManager->create($request);
    }

    /**
     * @Route("admin/tenants/{id}/delete", name="admin_tenant_delete", methods="DELETE")
     * @param Request $request
     * @param TenantManager $tenantManager
     * @return Response
     */
    public function delete(Request $request, TenantManager $tenantManager): Response
    {
        return $tenantManager->delete($request);
    }
}