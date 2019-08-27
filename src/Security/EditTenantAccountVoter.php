<?php

namespace App\Security;

use App\Entity\Account;
use App\Entity\Role;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditTenantAccountVoter extends Voter
{
    const EDIT = 'edit';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * EditTenantUserVoter constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        $this->em = $em;
        $this->userService = $userService;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if ($attribute != self::EDIT) {
            return false;
        }

        if (!$subject instanceof Account) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $loggedUser = $token->getUser();

        if (!$loggedUser instanceof Account) {
            return false;
        }

        /** @var Account $account */
        $account = $subject;
        $loggedUserRole = $this->userService->getAccountMainRole($loggedUser);

        if ($loggedUser->getTenant()->getId() == $account->getTenant()->getId()) {
            foreach ($account->getRoles() as $accountRole) {
                if (in_array($accountRole, Role::$tenantRolesRelation[$loggedUserRole])) {
                    return true;
                }
            }
        }
        return false;
    }
}