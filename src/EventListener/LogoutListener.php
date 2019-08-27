<?php

namespace App\EventListener;

use App\Form\EventListener\AbstractLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutListener extends AbstractLogger implements LogoutHandlerInterface
{
    /**
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     * @return void
     */
    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        $this->logger->info($this->translator->trans('gelf_logger.logout', ['%name%' => $token->getUsername()]));
    }
}