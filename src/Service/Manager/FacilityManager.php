<?php

namespace App\Service\Manager;

use App\Entity\Account;
use App\Entity\AccountFacilityRole;
use App\Entity\Facility;
use App\Entity\Report;
use App\Entity\Role;
use App\Entity\Routine;
use App\Entity\RoutineTemplate;
use App\Form\FacilityType;
use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FacilityManager extends AbstractManager
{
    /**
     * @param int|null $resourceId
     * @param int $page
     * @param array $options
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function listReports(int $resourceId = null, int $page, array $options = []): Response
    {
        /** @var Account $loggedUser */
        $loggedUser =  $this->tokenStorage->getToken()->getUser();
        $tenant = $loggedUser->getTenant();
        $facility = $this->em->getRepository(Facility::class)->getFacilityByIdAndAccountRoles($resourceId, $loggedUser, [Role::ROLE_TENANT_USER]);

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found.');
        }

        $facilityLayout = $facility->getFacilityLayouts()->last();

        //Allowed days in past to make a report
        $daysInPast = $this->reportService->getDaysInPast($facilityLayout);

        if ($facility->getTenant()->getId() != $tenant->getId()) {
            throw new AccessDeniedHttpException();
        }

        $facilityRepository = $this->em->getRepository(Facility::class);
        $sumTipsCurrentMonth = $facilityRepository->getSumTipsCurrentFacility(
            (new \DateTime('first day of this month'))->format('Y-m-d'),
            (new \DateTime('now'))->format('Y-m-d'),
            $facility->getId()
        );
        $sumTipsLastMonth = $facilityRepository->getSumTipsCurrentFacility(
            (new \DateTime('first day of last month'))->format('Y-m-d'),
            (new \DateTime('last day of last month'))->format('Y-m-d'),
            $facility->getId()
        );

        $categoryTips = $this->facilityService->getCategoryNameTips(array_merge($sumTipsCurrentMonth, $sumTipsLastMonth));
        $sumTips = $this->facilityService->transformArraySumTips($sumTipsCurrentMonth, $sumTipsLastMonth);

        $content = $this->templatingEngine->render(
            'facility/reports.html.twig',
            [
                'data' => $this->getPaginatedData($page, ['facility' => $facility, 'approved' => $options['approved']]),
                'facility' => $facility,
                'categoryTips' => $categoryTips,
                'sumTips' => $sumTips,
                'daysInPast' => $daysInPast,
                'approved' => $options['approved']
            ]
        );

        return new Response($content);
    }

    /**
     * @param int|null $resourceId
     * @param int $page
     * @param array $options
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function list(int $resourceId = null, int $page, array $options = []): Response
    {
        $tenant = $this->tokenStorage->getToken()->getUser()->getTenant();

        if (!$tenant) {
            throw new AccessDeniedHttpException();
        }

        $queryBuilder = $this->em->getRepository(Facility::class)
            ->findAllTenantFacilitiesQueryBuilder($tenant);

        $pager = new Pagerfanta(new DoctrineORMAdapter($queryBuilder));
        $pager->setMaxPerPage($this->params->get('max_items_in_list'));

        try {
            $pager->setCurrentPage($page);
        } catch(NotValidCurrentPageException $e) {
            throw new NotFoundHttpException('Wrong page');
        }

        $content = $this->templatingEngine->render('facility/list.html.twig', [
            'paginated_data' => $pager,
            'tenant' => $tenant
        ]);

        return new Response($content);
    }


    /**
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function create(Request $request): Response
    {
        $facility = new Facility();
        $form = $this->formFactory->create(FacilityType::class, $facility);
        $form->handleRequest($request);

        $tenant = $this->tokenStorage->getToken()->getUser()->getTenant();

        if (!$tenant) {
            throw new AccessDeniedHttpException();
        }

        $accountsToAssign = $this->em->getRepository(Account::class)->findBy(['tenant' => $tenant->getId()]);

        if ($form->isSubmitted()) {
            $data = $request->request->all();
            $validAssignUsers = $this->facilityService->issetUsersAndRoles($data);

            if ($form->isValid() && $validAssignUsers) {
                $facility->setCreatedAt(new \DateTimeImmutable());
                $facility->setModifiedAt(new \DateTimeImmutable());
                $facility->setType('gastronomy');
                $facility->setTenant($tenant);
                $facility->setEnableInterface(false);

                if ($data['facility']['routineTemplate']) {
                    /** @var RoutineTemplate $routineTemplate */
                    $routineTemplate = $this->em->getRepository(RoutineTemplate::class)->find($data['facility']['routineTemplate']);
                    if ($routineTemplate) {
                        $routine = new Routine();
                        $routine->setRoutineTemplate($routineTemplate);
                        $routine->setName($routineTemplate->getName());
                        $routine->setParams($routineTemplate->getParamTemplate());
                        $facility->setRoutine($routine);
                        $this->em->persist($routine);
                    }
                }

                //Add 21 items for newly created Facility
                $this->costForecastService->addCostForecastForNewFacility($facility);
                $this->facilityRoutineParamHandler->addExtraParams($facility);

                //Assign users to the Facility
                $this->facilityService->assignUsers($facility, $data);

                return new RedirectResponse($this->router->generate('tenant_facility'));
            }
        }

        $content = $this->templatingEngine->render('facility/add.html.twig', [
            'facility' => $facility,
            'form' => $form->createView(),
            'tenant' => $tenant,
            'accountsToAssign'=> $accountsToAssign,
            'validAssignUsers' => isset($validAssignUsers) ? $validAssignUsers : true,
        ]);

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function edit(Request $request): Response
    {
        $facility = $this->em->getRepository(Facility::class)->findOneBy(['id' => $request->get('facilityId'), 'deletedAt' => null]);

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found.');
        }
        $form = $this->formFactory->create(FacilityType::class, $facility);
        $form->handleRequest($request);

        $tenant = $this->tokenStorage->getToken()->getUser()->getTenant();

        if (!$tenant || $facility->getTenant()->getId() != $tenant->getId()) {
            throw new AccessDeniedHttpException();
        }

        $accountsToAssign = $this->em->getRepository(Account::class)->findBy(
            [
                'tenant' => $tenant->getId()
            ]
        );

        $assignedUsers = $this->facilityService->getAssignedUsers($facility);

        if ($form->isSubmitted()) {
            $validAssignUsers = $this->facilityService->issetUsersAndRoles($request->request->all());

            if ($form->isValid() && $validAssignUsers) {
                $this->em->persist($facility);
                $this->em->flush();

                $this->facilityService->assignUsers($facility, $request->request->all());

                return new RedirectResponse($this->router->generate('tenant_facility'));
            }
        }

        $content = $this->templatingEngine->render(
            'facility/edit.html.twig',
            [
                'facility' => $facility,
                'tenant' => $tenant,
                'form' => $form->createView(),
                'assignedUsers' => $assignedUsers,
                'accountsToAssign'=> $accountsToAssign,
                'validAssignUsers' => isset($validAssignUsers) ? $validAssignUsers : true,
            ]
        );

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function delete(Request $request): Response
    {
        /** @var Facility $facility */
        $facility = $this->em->getRepository(Facility::class)->find($request->get('id'));
        /** @var Account $loggedUser */
        $loggedUser = $this->tokenStorage->getToken()->getUser();

        if (!$facility) {
            throw new NotFoundHttpException('Facility not found.');
        }

        if ($facility->getTenant()->getId() != $loggedUser->getTenant()->getId()) {
            throw new AccessDeniedHttpException();
        }

        $accountFacilitiesRoles = $this->em->getRepository(AccountFacilityRole::class)->findBy(['facility' => $facility]);

        $this->em->getConnection()->beginTransaction();
        try {
            if ($accountFacilitiesRoles) {
                /** @var AccountFacilityRole $accountFacilitiesRole */
                foreach ($accountFacilitiesRoles as $accountFacilitiesRole) {
                    $this->em->remove($accountFacilitiesRole);
                }
            }

            $facility->setDeletedAt(new \DateTimeImmutable());
            $this->em->persist($facility);
            $this->em->flush();

            $this->em->getConnection()->commit();

            return new JsonResponse(['result' => 'success']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            return new JsonResponse(['result' => 'fail']);
        }
    }

    /**
     * @param int $page
     * @param array $params
     * @return Pagerfanta
     */
    protected function getPaginatedData(int $page, array $params = []): Pagerfanta
    {
        $paginatedData = null;
        $listQueryBuilder = $this->getListQueryBuilder($params);
        $paginatedData = new Pagerfanta(new \App\Paginator\Adapter\DoctrineDbalAdapter($listQueryBuilder, function ($queryBuilder) {
            $queryBuilder->select('COUNT(DISTINCT report.id) AS total_results');
        }));
        $paginatedData->setMaxPerPage($this->params->get('max_items_in_list'));
        $paginatedData->setCurrentPage($page);

        return $paginatedData;
    }

    /**
     * @param array $params
     * @return QueryBuilder
     */
    public function getListQueryBuilder(array $params = []): QueryBuilder
    {
        return $this->em->getRepository(Report::class)->getReportsListQueryBuilder($params['facility'], $params['approved']);
    }
}
