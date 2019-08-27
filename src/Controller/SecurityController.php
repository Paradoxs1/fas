<?php

namespace App\Controller;

use App\Service\GelfLogger;
use App\Service\LoginFailedAttemptsProcessorService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\AccountPasswordResetProcessorService;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @param AuthenticationUtils $authenticationUtils
     * @param LoginFailedAttemptsProcessorService $loginFailedProcessor
     * @param GelfLogger $logger
     * @param TranslatorInterface $translator
     * @return bool|Response
     */
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        LoginFailedAttemptsProcessorService $loginFailedProcessor,
        GelfLogger $logger,
        TranslatorInterface $translator
    ) {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $result = $loginFailedProcessor->process($lastUsername);
        if ($result instanceof Response) {
            return $result;
        }

        if (!is_null($error)) {
            $logger->warn($translator->trans('gelf_logger.failed_login', ['%name%' => $error->getToken()->getUsername()]));
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function pageNotFound()
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw new NotFoundHttpException();
        }

        return $this->redirectToRoute('login');
    }

    /**
     * @Route("/password/reset", name="password_reset")
     */
    public function passwordReset(Request $request, AccountPasswordResetProcessorService $passwordResetProcessor): Response
    {
        return $passwordResetProcessor->requestPasswordResetToken($request);
    }

    /**
     * @Route("/password/sent", name="password_sent")
     */
    public function passwordSent() {
        return $this->render('security/password-sent.html.twig');
    }

    /**
     * @Route("/password/expired", name="password_expired")
     */
    public function passwordExpired() {
        return $this->render('security/password-expired.html.twig');
    }

    /**
     * @Route("/password/set/{token}", name="password_set")
     */
    public function passwordSet(Request $request, AccountPasswordResetProcessorService $passwordResetProcessor) {
        return $passwordResetProcessor->resetPassword($request);
    }

    /**
     * @Route("/password/set-success", name="password_set_success")
     */
    public function passwordSetSuccess() {
        return $this->render('security/password-set-success.html.twig');
    }
}