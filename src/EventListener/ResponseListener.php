<?php

namespace App\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Doctrine\ORM\EntityManagerInterface;

class ResponseListener
{
    /**
     * @var mixed
     */
    private $user = '';

    /**
     * @var mixed
     */
    private $tokenStorage;

    /**
     * @var mixed
     */
    private $entityManager;

    /**
     * ResponseListener constructor.
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;

        if ($this->tokenStorage->getToken()) {
            $this->user = $this->tokenStorage ->getToken()->getUser();
        }
    }

    /**
     * @return bool
     */
    public function onKernelResponse()
    {
        if ('anon.' == $this->user || '' == $this->user) {
            return false;
        }

        if ($this->user->getRolesChanged()) {
            $token = new UsernamePasswordToken($this->user, null, 'main', $this->user->getRoles());
            $this->tokenStorage->setToken($token);

            $this->user->setRolesChanged(false);
            $this->entityManager->persist($this->user);
            $this->entityManager->flush($this->user);
        }

        return true;
    }
}