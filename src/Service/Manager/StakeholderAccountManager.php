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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StakeholderAccountManager extends AbstractAccountManager
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
        $loggedUser =  $this->tokenStorage->getToken()->getUser();
        $tenant = $loggedUser->getTenant();
        $facility = $this->em->getRepository(Facility::class)->getFacilityByIdAndAccountRoles($resourceId, $loggedUser, [Role::ROLE_TENANT_MANAGER]);

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found.');
        }

        if ($facility->getTenant()->getId() != $tenant->getId()) {
            throw new AccessDeniedHttpException();
        }

        $content = $this->templatingEngine->render(
            'facility/stakeholders.html.twig',
            [
                'data' => $this->getPaginatedData($page, ['facility' => $facility]),
                'facility' => $facility,
            ]
        );

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        /** @var Account $loggedUser */
        $loggedUser = $this->tokenStorage->getToken()->getUser();

        /** @var Facility $facility */
        $facility = $this->em->getRepository(Facility::class)->getFacilityByIdAndAccountRoles($request->get('id'), $loggedUser, [Role::ROLE_TENANT_MANAGER]);

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found.');
        }

        if ($facility->getTenant()->getId() != $loggedUser->getTenant()->getId()) {
            throw new AccessDeniedHttpException();
        }

        $account = new Account();
        $form = $this->formFactory->create(BaseAccountType::class, $account, ['validation_groups' => ['add']]);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->em->getConnection()->beginTransaction();
            try {
                $password = $this->passwordEncoder->encodePassword($account, $form->getData()->getPasswordHash());
                $account->setPasswordHash($password);
                $account->setTenant($facility->getTenant());
                $this->save($account);

                /** @var Role $role */
                $role = $this->em->getRepository(Role::class)->findOneByName(Role::ROLE_FACILITY_STAKEHOLDER);
                $accountFacilities[]= ['facility' => $facility->getId(), 'role' => [$role->getId()]];
                $this->setAccountFacilities($accountFacilities, $account);
                $this->em->getConnection()->commit();

                return new RedirectResponse($this->router->generate('stakeholder_accounts', ['id' => $facility->getId()]));
            } catch (\Exception $e) {
                $this->em->getConnection()->rollBack();
            }
        }

        $content = $this->templatingEngine->render(
            'facility/stakeholder-add.html.twig',
            [
                'form' => $form->createView(),
                'facility' => $facility,
            ]
        );

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request): Response
    {
        /** @var Account $loggedUser */
        $loggedUser = $this->tokenStorage->getToken()->getUser();

        /** @var Facility $facility */
        $facility = $this->em->getRepository(Facility::class)->getFacilityByIdAndAccountRoles($request->get('id'), $loggedUser, [Role::ROLE_TENANT_MANAGER]);

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found.');
        }

        /** @var Account $account */
        $account = $this->em->getRepository(Account::class)->getAccountByFacilityAndRoles($request->get('userId'), $facility, [Role::ROLE_FACILITY_STAKEHOLDER]);

        if (!$account) {
            throw new NotFoundHttpException('Account not found.');
        }

        if ($facility->getTenant()->getId() != $loggedUser->getTenant()->getId()) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->formFactory->create(BaseAccountType::class, $account, ['validation_groups' => ['edit', 'password']]);

        $accountFacilities = $this->prepareAccountFacilitiesData($account);
        $password = $account->getPasswordHash();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->em->getConnection()->beginTransaction();
            try {
                if ($form->getData()->getPasswordHash()) {
                    $password = $this->passwordEncoder->encodePassword($account, $form->getData()->getPasswordHash());
                }
                $account->setPasswordHash($password);
                $this->save($account);
                $this->em->getConnection()->commit();

                return new RedirectResponse($this->router->generate('stakeholder_accounts', ['id' => $facility->getId()]));
            } catch (\Exception $e) {
                $this->em->getConnection()->rollBack();
            }
        }

        $content = $this->templatingEngine->render(
            'facility/stakeholder-edit.html.twig',
            [
                'form' => $form->createView(),
                'facility' => $facility,
                'account' => $account
            ]
        );

        return new Response($content);
    }

    /**
     * @param array $params
     * @return QueryBuilder
     */
    public function getListQueryBuilder(array $params = []): QueryBuilder
    {
        return $this->em->getRepository(Account::class)->getFacilityUsersByRoles($params['facility'], [Role::ROLE_FACILITY_STAKEHOLDER]);
    }
}