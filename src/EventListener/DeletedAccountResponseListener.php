<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DeletedAccountResponseListener
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
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * ResponseListener constructor.
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage, UrlGeneratorInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;

        if ($this->tokenStorage->getToken()) {
            $this->user = $this->tokenStorage ->getToken()->getUser();
        }
    }

    /**
     * @return bool
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ('anon.' == $this->user || '' == $this->user) {
            return false;
        }

        if ($this->user->getDeletedAt() !== null) {
            $this->tokenStorage->setToken(null);
            $event->setResponse(new RedirectResponse($this->router->generate('login')));
        }

        return;
    }
}