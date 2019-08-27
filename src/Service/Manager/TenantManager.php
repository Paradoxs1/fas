<?php

namespace App\Service\Manager;

use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\FacilityLayout;
use App\Entity\Tenant;
use App\Form\TenantType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantManager extends AbstractManager
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
            'tenant/list.html.twig',
            [
                'paginated_data' => $this->getPaginatedData($page),
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
        $tenant = new Tenant();
        $form = $this->formFactory->create(TenantType::class, $tenant);
        $tenantManagers = $this->em->getRepository(Account::class)->findAllTenantManagersForTenantToAdd();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $tenantManagersToAssign = $request->request->get('tenantData')['manager'] ?: [];

            //TODO Ref.
            if ($tenantManagers) {
                foreach ($tenantManagers as $tenantManager) {
                    if (array_key_exists($tenantManager->getId(), $tenantManagersToAssign)) {
                        $tenantManager->setTenant($tenant);
                    }
                    $this->em->persist($tenantManager);
                }
            }
            $this->save($tenant);

            // create default configuration
            $facilityLayout = $this->em->getRepository(FacilityLayout::class)->findOneBy(['tenant' => $tenant, 'facility' => null]);
            if (!$facilityLayout) {
                $facilityLayout = new FacilityLayout();
                $facilityLayout->setTenant($tenant);
                $facilityLayout->setShifts(0);
                $facilityLayout->setDaysInPast(0);
                $this->em->persist($facilityLayout);
                $this->em->flush();
            }

            return new RedirectResponse($this->router->generate('admin_tenants'));
        }

        $content = $this->templatingEngine->render(
            'tenant/add.html.twig',
            [
                'tenant' => $tenant,
                'tenantManagers' => $tenantManagers,
                'form' => $form->createView(),
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
        /** @var Tenant $tenant */
        $tenant = $this->em->getRepository(Tenant::class)->find($request->get('id'));

        if (!$tenant) {
            throw new NotFoundHttpException('Tenant not found.');
        }

        $form = $this->formFactory->create(TenantType::class, $tenant);
        $tenantManagers = $this->em->getRepository(Account::class)->findAllTenantManagersForTenantEdit();
        $tenantLastManagers = $this->em->getRepository(Account::class)->findAllTenantManagersForTenantEdit($tenant->getId());

        foreach ($tenantLastManagers as $key => $tenantLastManager) {
            $tenantLastManagers[$tenantLastManager->getId()] = $tenantLastManager;
            unset($tenantLastManagers[$key]);
        }

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $tenantManagersToAssign = $request->request->get('tenantData')['manager'] ?: [];

            foreach ($tenantManagers as $tenantManager) {
                if (array_key_exists($tenantManager->getId(), $tenantManagersToAssign)) {
                    $tenantManager->setTenant($tenant);
                } elseif (!array_key_exists($tenantManager->getId(), $tenantManagersToAssign) && array_key_exists($tenantManager->getId(), $tenantLastManagers)) {
                    $tenantManager->setTenant(null);
                }
                $this->em->persist($tenantManager);
            }

            $this->em->flush();

            return new RedirectResponse($this->router->generate('admin_tenants'));
        }

        $content = $this->templatingEngine->render(
            'tenant/edit.html.twig',
            [
                'tenant' => $tenant,
                'tenantManagers' => $tenantManagers,
                'form' => $form->createView(),
            ]
        );

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        /** @var Tenant $tenant */
        $tenant = $this->em->getRepository(Tenant::class)->find($request->get('id'));

        if (!$tenant) {
            throw new NotFoundHttpException('Tenant not found.');
        }

        $facilities = $this->em->getRepository(Facility::class)->findBy(['tenant' => $tenant]);

        $this->em->getConnection()->beginTransaction();
        try {
            if ($facilities) {
                /** @var Facility $facility */
                foreach ($facilities as $facility) {
                    $facility->setDeletedAt(new \DateTimeImmutable());
                    $this->em->persist($facility);
                }
            }

            $accounts = $this->em->getRepository(Account::class)
                ->getTenantUsersByRoles($tenant)
                ->getQuery()
                ->getResult();

            if ($accounts) {
                /** @var Account $account */
                foreach ($accounts as $account) {
                    $account->setDeletedAt(new \DateTimeImmutable());
                    $this->em->persist($account);
                }
            }

            $tenant->setDeletedAt(new \DateTimeImmutable());
            $this->em->persist($tenant);
            $this->em->flush();

            $this->em->getConnection()->commit();

            return new JsonResponse(['result' => 'success']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            return new JsonResponse(['result' => 'fail']);
        }
    }

    /**
     * @param array $params
     * @return QueryBuilder
     */
    public function getListQueryBuilder(array $params = []): QueryBuilder
    {
        return $this->em->getRepository(Tenant::class)->findAllTenantsQueryBuilder();
    }
}
