<?php

namespace App\Service\Manager;

use App\Entity\Account;
use App\Entity\AccountFacilityRole;
use App\Entity\Facility;
use App\Entity\Role;
use App\Service\UserService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

abstract class AbstractAccountManager extends AbstractManager
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
     * @var SessionInterface
     */
    protected $session;

    /**
     * AbstractAccountManager constructor.
     * @param EntityManagerInterface $em
     * @param FormFactoryInterface $formFactory
     * @param UrlGeneratorInterface $router
     * @param \Twig_Environment $templatingEngine
     * @param UserService $userService
     * @param Connection $connection
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ParameterBagInterface $params
     * @param SessionInterface $session
     */
    public function __construct(
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $router,
        \Twig_Environment $templatingEngine,
        UserService  $userService,
        Connection $connection,
        UserPasswordEncoderInterface $passwordEncoder,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        ParameterBagInterface $params,
        SessionInterface $session
    )
    {
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->templatingEngine = $templatingEngine;
        $this->userService = $userService;
        $this->connection = $connection;
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->params = $params;
        $this->session = $session;
    }

    /**
     * @param array $accountFacilities
     * @param Account $account
     */
    protected function setAccountFacilities(array $accountFacilities, Account $account)
    {
        $facilityRepository = $this->em->getRepository(Facility::class);
        $roleRepository = $this->em->getRepository(Role::class);
        $result = [];
        foreach ($accountFacilities as $item) {
            if (isset($item['facility']) && isset($item['role']) && count($item['role']) > 0) {
                if (!isset($result[$item['facility']])) {
                    $result[$item['facility']] = [
                        'facility' => $item['facility'],
                        'roles' => $item['role']
                    ];
                }
            }
        }

        if ($result) {
            foreach ($result as $facilityId => $row) {
                foreach ($row['roles'] as $roleId) {
                    $facility = $facilityRepository->find($facilityId);
                    $role = $roleRepository->find($roleId);

                    $accountFacilityRole = new AccountFacilityRole();
                    $accountFacilityRole->setAccount($account);
                    $accountFacilityRole->setFacility($facility);
                    $accountFacilityRole->setRole($role);
                    $account->addAccountFacilityRole($accountFacilityRole);
                    $account->setRolesChanged(true);
                    $this->save($account);
                }
            }
        } else {
            throw new \InvalidArgumentException('Please assign at least one user to facility');
        }
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
     * @param Account $account
     * @return array
     */
    protected function prepareAccountFacilitiesData(Account $account, array $facilities = []): array
    {
        $result = [];
        if ($accountFacilities = $account->getAccountFacilityRoles()) {
            foreach ($accountFacilities as $accountFacility) {
                if ($accountFacility->getFacility()) {
                    $result[$accountFacility->getFacility()->getId()]['role'][] = $accountFacility->getRole()->getId();
                }
            }
        }

        if ($facilities) {
            $filteredResult = [];
            foreach($facilities as $facility) {
                if (in_array($facility->getId(), array_keys($result))) {
                    $filteredResult[$facility->getId()] = $result[$facility->getId()];
                }
            }
            return $filteredResult;
        }

        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    protected function prepareFormFacilitiesData($data): array
    {
        $result = [];
        if (count($data) > 0) {
            foreach ($data as $row) {
                $result[$row['facility']]['role'] = $row['role'];
            }
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        /** @var Account $account */
        $account = $this->em->getRepository(Account::class)->find($request->get('id'));
        $account->setDeletedAt(new \DateTimeImmutable());
        $this->save($account);

        return new JsonResponse(['result' => 'success']);
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
}
