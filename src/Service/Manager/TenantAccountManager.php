<?php

namespace App\Service\Manager;

use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\Role;
use App\Form\BaseAccountType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantAccountManager extends AbstractAccountManager
{
    /**
     * @param int|null $resourceId
     * @param int $page
     * @param array $options
     * @return Response
     */
    public function list(int $resourceId = null, int $page, array $options = []): Response
    {
        /** @var Account $loggedUser */
        $loggedUser = $this->tokenStorage->getToken()->getUser();
        $tenant = $loggedUser->getTenant();
        $role = $this->userService->getAccountMainRole($loggedUser);
        $paginatedData = null;
        $roles = Role::$tenantRolesRelation[Role::ROLE_TENANT_MANAGER];

        if ($tenant) {
            if ($role == Role::ROLE_TENANT_USER) {
                $roles = Role::$tenantRolesRelation[Role::ROLE_TENANT_USER];
            }
        }

        $content = $this->templatingEngine->render(
            'tenant/users.html.twig',
            [
                'data' => $this->getPaginatedData($page, ['tenant' => $tenant, 'roles' => $roles])
            ]
        );

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function create(Request $request): Response
    {
        /** @var Account $loggedUser */
        $loggedUser = $this->tokenStorage->getToken()->getUser();
        $account = new Account();

        $form = $this->formFactory->create(BaseAccountType::class, $account, ['validation_groups' => ['add']]);
        $role = $this->userService->getAccountMainRole($loggedUser);

        $roles = $accountFacilities = [];
        if ($role == Role::ROLE_TENANT_USER) {
            $roles[] = Role::ROLE_TENANT_USER;
        }

        $facilities = $this
            ->em
            ->getRepository(Facility::class)
            ->getTenantFacilitiesByAccountRoles($loggedUser, $roles);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->em->getConnection()->beginTransaction();
            try {
                $password = $this->passwordEncoder->encodePassword($account, $form->getData()->getPasswordHash());
                $account->setPasswordHash($password);
                $account->setTenant($loggedUser->getTenant());

                $accountFacilities = $request->get('fas_tenant_account')['accountFacilityRoles'];
                $this->setAccountFacilities($accountFacilities, $account);
                $this->em->getConnection()->commit();

                return new RedirectResponse($this->router->generate('tenant_accounts'));
            } catch (\Exception $e) {
                $this->em->getConnection()->rollBack();
                $this->session->getFlashBag()->set('danger', $e->getMessage());
            }
        }

        $accountFacilities = $request->get('fas_tenant_account')['accountFacilityRoles'];
        $content = $this->templatingEngine->render(
            'tenant/users-add.html.twig',
            [
                'form' => $form->createView(),
                'facilities' => $facilities,
                'accountFacilities' => $accountFacilities,
                'roles' => Role::$tenantRolesRelation[$role]
            ]
        );

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function edit(Request $request): Response
    {
        /** @var Account $loggedUser */
        $loggedUser = $this->tokenStorage->getToken()->getUser();

        /** @var Account $account */
        $account = $this->em->getRepository(Account::class)->findOneBy(['id' => $request->get('id'), 'deletedAt' => null]);

        if (!$account) {
            throw new NotFoundHttpException('Account not found.');
        }

        if ($this->authorizationChecker->isGranted('edit', $account) == false) {
            throw new NotFoundHttpException('Account not found.');
        }

        $form = $this->formFactory->create(BaseAccountType::class, $account, ['validation_groups' => ['edit', 'password']]);
        $role = $this->userService->getAccountMainRole($loggedUser);

        $roles = $accountFacilities = [];
        if ($role == Role::ROLE_TENANT_USER) {
            $roles[] = Role::ROLE_TENANT_USER;
        }

        $facilities = $this
            ->em
            ->getRepository(Facility::class)
            ->getTenantFacilitiesByAccountRoles($loggedUser, $roles);

        $accountFacilities = $this->prepareAccountFacilitiesData($account, $facilities);
        $password = $account->getPasswordHash();

        if ($request->isMethod('POST')) {
            if ($form->handleRequest($request)->isValid()) {
                $this->em->getConnection()->beginTransaction();
                try {
                    if ($form->getData()->getPasswordHash()) {
                        $password = $this->passwordEncoder->encodePassword($account, $form->getData()->getPasswordHash());
                    }
                    $account->setPasswordHash($password);
                    $account->setTenant($loggedUser->getTenant());
                    $this->save($account);

                    foreach ($account->getAccountFacilityRoles() as $facilityRole) {
                        if (in_array($facilityRole->getRole()->getInternalName(), Role::$tenantRolesRelation[$role])){
                            $this->em->remove($facilityRole);
                        }
                    }
                    $this->em->flush();

                    $accountFacilities = $request->get('fas_tenant_account')['accountFacilityRoles'];

                    $this->setAccountFacilities($accountFacilities, $account);
                    $this->em->getConnection()->commit();

                    return new RedirectResponse($this->router->generate('tenant_accounts'));
                } catch (\Exception $e) {
                    $this->em->getConnection()->rollBack();
                }
            } else {
                $accountFacilities = $this->prepareFormFacilitiesData($request->get('fas_tenant_account')['accountFacilityRoles']);
            }
        }

        $content = $this->templatingEngine->render(
            'tenant/users-edit.html.twig',
            [
                'form' => $form->createView(),
                'facilities' => $facilities,
                'accountFacilities' => $accountFacilities,
                'roles' => Role::$tenantRolesRelation[$role],
                'account' => $account
            ]
        );

        return new Response($content);
    }

    public function getListQueryBuilder(array $params = []): QueryBuilder
    {
        return $this->em->getRepository(Account::class)->getTenantUsersByRoles($params['tenant'], $params['roles']);
    }
}
