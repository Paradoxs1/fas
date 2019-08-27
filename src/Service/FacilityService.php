<?php

namespace App\Service;

use App\Entity\Facility;
use App\Entity\Account;
use App\Entity\CostForecastWeekDay;
use App\Entity\Role;
use App\Entity\AccountFacilityRole;

use App\Repository\AccountRepository;
use App\Repository\RoleRepository;
use App\Repository\AccountFacilityRoleRepository;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class FacilityService
 * @package App\Service
 */
class FacilityService
{
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var AccountFacilityRoleRepository
     */
    private $accountFacilityRoleRepository;

    /**
     * @var MoneyService
     */
    private $moneyService;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * FacilityService constructor.
     * @param AccountRepository $accountRepository
     * @param ObjectManager $manager
     * @param RoleRepository $roleRepository
     */
    public function __construct(
        ObjectManager $manager,
        AccountRepository $accountRepository,
        RoleRepository $roleRepository,
        AccountFacilityRoleRepository $accountFacilityRoleRepository,
        MoneyService $moneyService
    ){
        $this->manager                       = $manager;
        $this->accountRepository             = $accountRepository;
        $this->roleRepository                = $roleRepository;
        $this->accountFacilityRoleRepository = $accountFacilityRoleRepository;
        $this->moneyService                  = $moneyService;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function issetUsersAndRoles(array $data): bool
    {
        if (
            !isset($data['user-to-assign']) || !isset($data['roles']) ||
            !$data['user-to-assign'] || !$data['roles']
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param Facility $facility
     * @param array $data
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function assignUsers(Facility $facility, array $data)
    {
        $this->issetUsersAndRoles($data);

        $accountFacilityRoles = $this->accountFacilityRoleRepository->findByFacility($facility);

        if ($accountFacilityRoles) {
            foreach ($accountFacilityRoles as $item) {
                $this->manager->remove($item);
            }

            $this->manager->flush();
        }

        foreach ($data['user-to-assign'] as $k => $userId) {
            $user = $this->accountRepository->find($userId);

            if (!$user) {
                continue;
            }

            //Needed to auto refresh assigned roles
            $user->setRolesChanged(true);
            $this->manager->persist($user);

            $rolesToAssign = [];

            if (isset($data['roles']['tenant-manager'][$k])) {
                $rolesToAssign[] =  $this->roleRepository->findOneByName('ROLE_TENANT_MANAGER');
            }

            if (isset($data['roles']['tenant-user'][$k])) {
                $rolesToAssign[] =  $this->roleRepository->findOneByName('ROLE_TENANT_USER');
            }

            if (isset($data['roles']['stakeholder'][$k])) {
                $rolesToAssign[] =  $this->roleRepository->findOneByName('ROLE_FACILITY_STAKEHOLDER');
            }

            if (isset($data['roles']['facility-manager-user'][$k])) {
                if ('fm' == $data['roles']['facility-manager-user'][$k]) {
                    $rolesToAssign[] =  $this->roleRepository->findOneByName('ROLE_FACILITY_MANAGER');
                } else if ('fu' == $data['roles']['facility-manager-user'][$k]) {
                    $rolesToAssign[] =  $this->roleRepository->findOneByName('ROLE_FACILITY_USER');
                }
            }

            foreach ($rolesToAssign as $role) {
                $this->assignUser($facility, $user, $role);
            }
        }

        return true;
    }

    /**
     * @param Facility $facility
     * @param Account $user
     * @param Role $role
     * @return bool
     */
    public function assignUser(Facility $facility, Account $user, Role $role)
    {
        $accountFacilityRole = new AccountFacilityRole();
        $accountFacilityRole->setFacility($facility);
        $accountFacilityRole->setAccount($user);
        $accountFacilityRole->setRole($role);

        $this->manager->persist($accountFacilityRole);
        $this->manager->flush($accountFacilityRole);
    }

    /**
     * @param Facility $facility
     */
    public function getAssignedUsers(Facility $facility)
    {
        $result = [];

        $data = $this->accountFacilityRoleRepository->findByFacility($facility);

        if ($data) {
            foreach ($data as $item) {
                $userId = $item->getAccount()->getId();
                $result[$userId]['id'] = $userId;

                $roleInternalName = $item->getRole()->getInternalName();

                if ('ROLE_TENANT_MANAGER' == $roleInternalName) {
                    $result[$userId]['tenant-manager'] = 1;
                }
                else if ('ROLE_TENANT_USER' == $roleInternalName) {
                    $result[$userId]['tenant-user'] = 1;
                }
                else if ('ROLE_FACILITY_STAKEHOLDER' == $roleInternalName) {
                    $result[$userId]['stakeholder'] = 1;
                }
                else if ('ROLE_FACILITY_MANAGER' == $roleInternalName) {
                    $result[$userId]['facility-manager'] = 1;
                }
                else if ('ROLE_FACILITY_USER' == $roleInternalName) {
                    $result[$userId]['facility-user'] = 1;
                }
            }
        }

        return $result;
    }

    /**
     * @param Account $user
     * @param Facility $facility
     * @param string $role
     * @return bool
     */
    public function hasRoleInFacility(Account $user, Facility $facility, $role = ''): bool
    {
        if (!$role) {
            return false;
        }

        $accountFacilityRoles = $user->getAccountFacilityRoles();

        if ($accountFacilityRoles) {
            foreach ($accountFacilityRoles as $item) {
                if (
                    $item->getFacility() !== null &&
                    $item->getFacility()->getId() == $facility->getId() &&
                    $item->getRole()->getInternalName() == $role
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function assignEstimatedCostsPerDay(array $data)
    {

        if (!isset($data['estimated-costs']) || !$data['estimated-costs']) {
            return false;
        }

        foreach ($data['estimated-costs'] as $costId => $data) {
            $costForecast = $this->manager->getRepository(CostForecastWeekDay::class)->find($costId);

            if (!$costForecast) {
                continue;
            }

            $value = $data[key($data)][key($data[key($data)])]['value'];

            $type  = $data[key($data)][key($data[key($data)])]['valueType'];
            $type  = in_array($type, ['relative', 'fix']) ? $type : 'fix';

            $value = $this->moneyService->valueToValidNumber($value, $type);

            $costForecast->setValue($value);
            $costForecast->setType($type);

            $this->manager->persist($costForecast);
        }

        $this->manager->flush();

        return true;
    }

    /**
     * @param array $sumTips
     * @return array
     */
    public function getCategoryNameTips(array $sumTips): array
    {
        $categoryTips = [];

        foreach ($sumTips as $item) {
            $categoryTips[] = $item['value'];
        }

        return array_unique($categoryTips);
    }

    /**
     * @param array $array
     * @return array
     */
    private function transformAndSumValueArray(array $array): array
    {
        $sumTips = [];

        foreach ($array as $item) {
            $item['sum'] = (float) str_replace("'",'', $item['sum']);

            if (isset($sumTips[$item['value']])) {
                $sumTips[$item['value']]['sum'] += $item['sum'];
            } else {
                $sumTips[$item['value']] = $item;
            }
        }

        return $sumTips;
    }

    /**
     * @param array $sumTipsCurrentMonth
     * @param array $sumTipsLastMonth
     * @return array
     */
    public function transformArraySumTips(array $sumTipsCurrentMonth, array $sumTipsLastMonth): array
    {
        $sumTips['currentMonth'] = $this->transformAndSumValueArray($sumTipsCurrentMonth);
        $sumTips['lastMonth'] = $this->transformAndSumValueArray($sumTipsLastMonth);

        return $sumTips;
    }
}
