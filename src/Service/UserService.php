<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\Facility;
use App\Entity\Role;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class UserService
 * @package App\Service
 */
class UserService
{
    /**
     * @param Account $user
     * @param string $specificRole
     * @return bool
     */
    public function hasSpecificRole(Account $user, $specificRole = ''): bool
    {
        $roles = $user->getRoles();

        if (!$roles || !$specificRole) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role == $specificRole) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Account $user
     * @return Facility|null
     */
    public function getFirstStakeholderFacility(Account $user): ?Facility
    {
        $accountFacilityRoles = $user->getAccountFacilityRoles();

        if ($accountFacilityRoles) {
            foreach ($accountFacilityRoles as $item) {
                if ('ROLE_FACILITY_STAKEHOLDER' == $item->getRole()->getInternalName()) {
                    return $item->getFacility();
                }
            }
        }

        return false;
    }

    /**
     * @param Account $user
     * @return Facility|null
     */
    public function getFacilityUserFacilityManagerFacility(Account $user): ?Facility
    {
        $accountFacilityRoles = $user->getAccountFacilityRoles();

        if ($accountFacilityRoles) {
            foreach ($accountFacilityRoles as $item) {
                $internalName = $item->getRole()->getInternalName();

                if (
                    'ROLE_FACILITY_USER' == $internalName ||
                    'ROLE_FACILITY_MANAGER' == $internalName
                ) {
                    return $item->getFacility();
                }
            }
        }

        return false;
    }

    /**
     * @param $security
     * @param $router
     * @param $tokenStorage
     * @return null|RedirectResponse
     */
    public function redirectUser($security, $router, $tokenStorage): ?RedirectResponse
    {
        if ($security->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($router->generate('admin_users'));
        }

        if ($security->isGranted('ROLE_TENANT_MANAGER') || $security->isGranted('ROLE_TENANT_USER')) {
            return new RedirectResponse($router->generate('tenant'));
        }

        if ($security->isGranted('ROLE_FACILITY_STAKEHOLDER')) {
            $user = $tokenStorage->getToken()->getUser();
            $facility = $this->getFirstStakeholderFacility($user);

            if ($facility) {
                return new RedirectResponse($router->generate('facility_statistics', [
                    'id' => $facility->getId()
                ]));
            }
        }

        if ($security->isGranted('ROLE_FACILITY_USER') || $security->isGranted('ROLE_FACILITY_MANAGER')) {
            return new RedirectResponse($router->generate('cashier_report'));
        }

        return new RedirectResponse($router->generate('login'));
    }

    /**
     * @param Account $account
     * @return null|string
     */
    public function getAccountMainRole(Account $account): ?string
    {
        $accountRoles = $account->getRoles();

        foreach (Role::$tenantRolesRelation as $role => $roles) {
            foreach ($accountRoles as $accountRole) {
                if ($accountRole == $role) {
                    return $role;
                }
            }
        }
        return null;
    }
}
