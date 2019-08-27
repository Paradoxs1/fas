<?php
namespace App\Controller\Admin;

use App\Service\Manager\Admin\TenantAccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Account;

/**
 * Class AdminUserController
 * @package Controller
 */
class UserController extends AbstractController
{
    /**
     * @Route("/admin/users", name="admin_users")
     * @param Request $request
     * @param TenantAccountManager $accountManager
     * @return Response
     */
    public function index(Request $request, TenantAccountManager $accountManager)
    {
        return $accountManager->list(null, $request->query->get('page', 1));
    }

    /**
     * @Route("admin/users/add", name="admin_account_new", methods="GET|POST")
     * @param Request $request
     * @param TenantAccountManager $accountManager
     * @return Response
     */
    public function create(Request $request, TenantAccountManager $accountManager): Response
    {
        return $accountManager->create($request);
    }

    /**
     * @Route("admin/users/{id}/edit", name="admin_user_edit", methods="GET|POST")
     * @param Request $request
     * @param TenantAccountManager $accountManager
     * @return Response
     */
    public function edit(Request $request, TenantAccountManager $accountManager): Response
    {
        return $accountManager->edit($request);
    }

    /**
     * @Route("/users/username/unique", name="account_username_unique", methods="POST")
     */
    public function userNameUnique(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_name']) || !$data['user_name']) {
            return new JsonResponse(['result' => 'failure']);
        }

        $user = $this->getDoctrine()->getRepository(Account::class)->findUserByLogin(trim($data['user_name']));

        if (!$user) {
            return new JsonResponse(['result' => 'success']);
        }

        if (isset($data['user_edit_id']) && $data['user_edit_id']) {
            if ($user->getId() != $data['user_edit_id']) {
                return new JsonResponse(['result' => 'failure']);
            }
        }

        return new JsonResponse([
            'result' => 'success'
        ]);
    }
}