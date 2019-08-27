<?php

namespace App\EventListener;

use App\Entity\Account;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;

class AuthenticationSuccessListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TokenStorageInterface
     */
    private $storage;

    /**
     * AuthenticationSuccessListener constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->storage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public function onSecurityInteractiveLogin()
    {
        /** @var Account $account */
        $account = $this->storage->getToken()->getUser();
        $loginAttempts = $account->getLoginAttempts();

        foreach ($loginAttempts as $loginAttempt) {
            $this->entityManager->remove($loginAttempt);
        }
        $this->entityManager->flush();
    }
}