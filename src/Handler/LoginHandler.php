<?php
/**
 * Created by PhpStorm.
 * User: artem
 * Date: 31.07.18
 * Time: 12:04
 */

namespace App\Handler;

use App\Service\GelfLogger;
use App\Service\UserService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Monolog\Logger;

/**
 * Class LoginHandler
 * @package App\Handler
 */
class LoginHandler implements AuthenticationFailureHandlerInterface, AuthenticationSuccessHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * LoginHandler constructor.
     * @param RouterInterface $router
     * @param Security $security
     * @param UserService $userService
     * @param TokenStorageInterface $tokenStorage
     * @param GelfLogger $gelfLogger
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RouterInterface $router,
        Security $security,
        UserService $userService,
        TokenStorageInterface $tokenStorage,
        GelfLogger $gelfLogger,
        TranslatorInterface $translator
    )
    {
        $this->router = $router;
        $this->security = $security;
        $this->userService = $userService;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $gelfLogger;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $referer = $request->headers->get('referer');

        return new RedirectResponse($referer);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $this->logger->info($this->translator->trans('gelf_logger.login', ['%name%' => $this->security->getUser()->getUsername()]));
        return $this->userService->redirectUser($this->security, $this->router,  $this->tokenStorage);
    }
}