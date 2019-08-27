<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\LoginAttempt;
use App\Entity\Account;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class AuthenticationFailureListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var int
     */
    private $loginAttempts;

    /**
     * AuthenticationFailureListener constructor.
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $entityManager
     * @param int $loginAttempts
     */
    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        int $loginAttempts
    ) {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->loginAttempts = $loginAttempts;
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onSecurityAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $request  = $this->requestStack->getCurrentRequest()->request;
        $username = $request->get('_username');

        /** @var Account $account */
        $account = $this->entityManager->getRepository(Account::class)->loadUserByUsername($username);

        if ($account) {
            /** @var LoginAttempt $loginAttempts */
            $loginAttempts = $account->getLoginAttempts();

            if (count($loginAttempts) < $this->loginAttempts) {
                $this->saveLoginAttempts($account);
            }
        }
    }

    /**
     * @param Account $account
     */
    private function saveLoginAttempts(Account $account)
    {
        $loginAttempt = new LoginAttempt();
        $loginAttempt->setAccount($account);

        $this->entityManager->persist($loginAttempt);
        $this->entityManager->flush();
    }
}