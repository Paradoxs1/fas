<?php

namespace App\Service\Manager\Admin;

use App\Entity\Account;
use App\Entity\AccountFacilityRole;
use App\Entity\Role;
use App\Form\AccountType;
use App\Service\Manager\AbstractAccountManager;
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
        $content = $this->templatingEngine->render(
            'account/list.html.twig',
            [
                'paginated_data' => $this->getPaginatedData($page)
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
        $account = new Account();
        $form = $this->formFactory->create(AccountType::class, $account, ['validation_groups' => ['add']]);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $userPassword = $this->passwordEncoder->encodePassword($account, $account->getPassword());
            $account->setPassword($userPassword);

            $tenantManagerRole = $this->em->getRepository(Role::class)->findOneByName('ROLE_TENANT_MANAGER');

            $accountFacilityRole = new AccountFacilityRole();
            $accountFacilityRole->setAccount($account);
            $accountFacilityRole->setRole($tenantManagerRole);
            $account->addAccountFacilityRole($accountFacilityRole);

            $this->em->persist($account);
            $this->em->flush();

            return new RedirectResponse($this->router->generate('admin_users'));
        }

        $content = $this->templatingEngine->render(
            'account/add.html.twig',
            [
                'form' => $form->createView(),
                'account' => $account,
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
        /** @var Account $account */
        $account = $this->em->getRepository(Account::class)->findOneBy(['id' => $request->get('id'), 'deletedAt' => null]);

        if (!$account) {
            throw new NotFoundHttpException('Account not found.');
        }

        if (!$this->userService->hasSpecificRole($account, 'ROLE_TENANT_MANAGER')) {
            throw new NotFoundHttpException('Account not found.');
        }

        $form = $this->formFactory->create(AccountType::class, $account, ['validation_groups' => ['edit', 'password']]);
        $password = $account->getPasswordHash();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            if ($form->getData()->getPasswordHash()) {
                $password = $this->passwordEncoder->encodePassword($account, $form->getData()->getPasswordHash());
            }
            $account->setPasswordHash($password);
            $this->em->flush();

            return new RedirectResponse($this->router->generate('admin_users'));
        }

        $content = $this->templatingEngine->render(
            'account/edit.html.twig',
            [
                'account' => $account,
                'form' => $form->createView(),
            ]
        );

        return new Response($content);
    }

    public function getListQueryBuilder(array $params = []): QueryBuilder
    {
        return $this->em->getRepository(Account::class)->findAllTenantManagersQueryBuilder();
    }
}