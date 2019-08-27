<?php

namespace App\Service\Manager;

use App\Service\CostForecastService;
use App\Service\Facility\Handler\FacilityRoutineParamHandler;
use App\Service\FacilityService;
use App\Service\FlexParamService;
use App\Service\ReportService;
use App\Service\UserService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;

abstract class AbstractManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var \Twig_Environment
     */
    protected $templatingEngine;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var FacilityService
     */
    protected $facilityService;

    /**
     * @var FlexParamService
     */
    protected $flexParamService;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var ParameterBagInterface
     */
    protected $params;

    /**
     * @var CostForecastService
     */
    protected $costForecastService;

    /**
     * @var FacilityRoutineParamHandler
     */
    protected $facilityRoutineParamHandler;

    /**
     * @var ReportService
     */
    protected $reportService;

    /**
     * AbstractManager constructor.
     * @param EntityManagerInterface $em
     * @param FormFactoryInterface $formFactory
     * @param UrlGeneratorInterface $router
     * @param \Twig_Environment $templatingEngine
     * @param UserService $userService
     * @param FacilityService $facilityService
     * @param FlexParamService $flexParamService
     * @param Connection $connection
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ParameterBagInterface $params
     * @param CostForecastService $costForecastService
     * @param FacilityRoutineParamHandler $facilityRoutineParamHandler
     * @param ReportService $reportService
     */
    public function __construct(
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $router,
        \Twig_Environment $templatingEngine,
        UserService  $userService,
        FacilityService $facilityService,
        FlexParamService $flexParamService,
        Connection $connection,
        UserPasswordEncoderInterface $passwordEncoder,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        ParameterBagInterface $params,
        CostForecastService $costForecastService,
        FacilityRoutineParamHandler $facilityRoutineParamHandler,
        ReportService $reportService
    ) {
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->templatingEngine = $templatingEngine;
        $this->userService = $userService;
        $this->facilityService = $facilityService;
        $this->flexParamService = $flexParamService;
        $this->connection = $connection;
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->params = $params;
        $this->costForecastService = $costForecastService;
        $this->facilityRoutineParamHandler = $facilityRoutineParamHandler;
        $this->reportService = $reportService;
    }

    /**
     * @param $entity
     */
    protected function save($entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
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
        $paginatedData = new Pagerfanta(new DoctrineORMAdapter($listQueryBuilder));
        $paginatedData->setMaxPerPage($this->params->get('max_items_in_list'));
        $paginatedData->setCurrentPage($page);

        return $paginatedData;
    }

    /**
     * @param int|null $resourceId
     * @param int $page
     * @param array $options
     * @return Response
     */
    abstract public function list(int $resourceId = null, int $page, array $options = []): Response;

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function create(Request $request): Response;

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function edit(Request $request): Response;

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function delete(Request $request): Response;

    /**
     * @param array $params
     * @return QueryBuilder|DBALQueryBuilder
     */
    abstract public function getListQueryBuilder(array $params = []);
}
