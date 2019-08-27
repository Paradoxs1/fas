<?php

namespace App\Twig;

use App\Entity\Role;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;

class AccountRoleExtension extends AbstractExtension
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * AccountRoleExtension constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param UserService $userService
     */
    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, UserService $userService)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->userService = $userService;
    }

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_roles_data', [$this, 'getRolesData']),
        ];
    }

    /**
     * @param string $type
     * @return array
     */
    public function getRolesData(string $type = 'tenant'): array
    {
        $data = [];
        $user = $this->tokenStorage->getToken()->getUser();
        $role = $this->userService->getAccountMainRole($user);

        if ($type == 'facility') {
            $roles = Role::$facilityRolesRelation[Role::ROLE_TENANT_USER];
        } elseif ($type == 'tenant') {
            $roles = Role::$tenantRolesRelation[$role];
        }

        $rolesData = $this->em->getRepository(Role::class)->getRolesByCodes($roles);

        foreach ($rolesData as $role) {
            $data[] = [
                'id' => $role->getId(),
                'title' => $role->getAdministrativeName(),
                'displayType' => $role->getDisplayType(),
            ];
        }

        return $data;
    }
}