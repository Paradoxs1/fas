<?php

namespace App\Service;

use App\Entity\Account;
use App\Form\AccountResetPasswordType;
use App\Form\Model\PasswordReset;
use App\Form\Model\PasswordResetRequest;
use App\Repository\AccountRepository;
use App\Service\Email\EmailSender;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Form\AccountRequestPasswordResetType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class AccountPasswordResetProcessorService
 * @package App\Service
 */
class AccountPasswordResetProcessorService
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var \Twig_Environment
     */
    private $templatingEngine;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var int
     */
    private $passwordResetTokenLifetime;

    /**
     * AccountPasswordResetProcessorService constructor.
     * @param FormFactoryInterface $formFactory
     * @param AccountRepository $accountRepository
     * @param ObjectManager $objectManager
     * @param UrlGeneratorInterface $router
     * @param \Twig_Environment $templatingEngine
     * @param EmailSender $emailSender
     * @param UserPasswordEncoderInterface $encoder
     * @param int $passwordResetTokenLifetime
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        AccountRepository $accountRepository,
        ObjectManager $objectManager,
        UrlGeneratorInterface $router,
        \Twig_Environment $templatingEngine,
        EmailSender $emailSender,
        UserPasswordEncoderInterface $encoder,
        int $passwordResetTokenLifetime
    )
    {
        $this->formFactory = $formFactory;
        $this->accountRepository = $accountRepository;
        $this->objectManager = $objectManager;
        $this->router = $router;
        $this->templatingEngine = $templatingEngine;
        $this->emailSender = $emailSender;
        $this->encoder = $encoder;
        $this->passwordResetTokenLifetime = $passwordResetTokenLifetime;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function requestPasswordResetToken(Request $request): Response
    {
        $passwordReset = new PasswordResetRequest();
        $form = $this->formFactory->create(AccountRequestPasswordResetType::class, $passwordReset);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $account = $this->accountRepository->findUserByLogin(strtolower($passwordReset->getUsername()));

            if (null === $account) {
                throw new NotFoundHttpException('Account not found.');
            }
            $account->setPasswordResetToken(md5(random_bytes(16)));
            $account->setPasswordRequestedAt(new \DateTime());
            $this->objectManager->persist($account);
            $this->objectManager->flush();

            $content = $this->templatingEngine->render('security/email/password-reset.html.twig', ['account' => $account]);
            $this->emailSender->send($content, $account->getAccountEmail()->getEmail(), 'FAS: Passwort zurücksetzen');

            return new RedirectResponse($this->router->generate('password_sent'));
        }

        $content = $this->templatingEngine->render(
            'security/password-reset.html.twig',
            [
                'form' => $form->createView(),
            ]
        );

        return new Response($content);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function resetPassword(Request $request): Response
    {
        $token = $request->get('token');
        $account = $this->accountRepository->findOneBy(['passwordResetToken' => $token]);

        if (null === $account) {
            throw new NotFoundHttpException('Token not found.');
        }

        $lifetime = new \DateInterval(sprintf('PT%sS', $this->passwordResetTokenLifetime));
        if (!$account->isPasswordRequestNonExpired($lifetime)) {
            return $this->handleExpiredToken($account);
        }

        $passwordReset = new PasswordReset();
        $form = $this->formFactory->create(AccountResetPasswordType::class, $passwordReset);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            return $this->handleResetPassword($request, $account, $passwordReset->getPassword());
        }

        $content = $this->templatingEngine->render(
            'security/password-set.html.twig',
            [
                'form' => $form->createView(),
            ]
        );

        return new Response($content);
    }

    /**
     * @param Account $account
     * @return Response
     */
    protected function handleExpiredToken(Account $account): Response
    {
        $account->setPasswordResetToken(null);
        $account->setPasswordRequestedAt(null);

        $this->objectManager->flush();

        return new RedirectResponse($this->router->generate('password_expired'));
    }

    /**
     * @param Request $request
     * @param Account $account
     * @param string $newPassword
     * @return Response
     */
    protected function handleResetPassword(Request $request, Account $account, string $newPassword): Response
    {

        $password = $this->encoder->encodePassword($account, $newPassword);
        $account->setPasswordHash($password);
        $account->setPasswordResetToken(null);
        $account->setPasswordRequestedAt(null);

        $this->objectManager->flush();

        return new RedirectResponse($this->router->generate('password_set_success'));
    }
}
