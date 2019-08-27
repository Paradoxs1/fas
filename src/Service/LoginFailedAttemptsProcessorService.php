<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\LoginAttempt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginFailedAttemptsProcessorService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var int
     */
    private $loginAttemptGap;

    /**
     * @var int
     */
    private $loginAttempts;

    /**
     * @var int
     */
    private $loginBanTime;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var \Twig_Environment
     */
    private $templatingEngine;

    /**
     * LoginFailedAttemptsProcessorService constructor.
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $router
     * @param \Twig_Environment $templatingEngine
     * @param int $loginAttemptGap
     * @param int $loginAttempts
     * @param int $loginBanTime
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $router,
        \Twig_Environment $templatingEngine,
        int $loginAttemptGap,
        int $loginAttempts,
        int $loginBanTime
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->templatingEngine = $templatingEngine;
        $this->loginAttemptGap = $loginAttemptGap;
        $this->loginAttempts = $loginAttempts;
        $this->loginBanTime = $loginBanTime;
    }

    /**
     * @param string $username
     * @return bool|Response
     */
    public function process(string $username)
    {
        /** @var Account $account */
        $account = $this->entityManager->getRepository(Account::class)->loadUserByUsername($username);

        if ($account) {
            /** @var LoginAttempt $firstLoginAttempt */
            $lastLoginAttempt = $this->entityManager->getRepository(LoginAttempt::class)->getLatestByAccount($account);

            if ($lastLoginAttempt) {
                $startDate = $endDate = $lastLoginAttempt->getCreatedAt();
                $startDate = $startDate->sub(new \DateInterval(sprintf('PT%sS', $this->loginAttemptGap)));
                $endDatePlusBanPeriod = $endDate->add(new \DateInterval(sprintf('PT%sS', $this->loginBanTime)));

                /** @var LoginAttempt $loginAttempts */
                $loginAttempts = $this->entityManager->getRepository(LoginAttempt::class)->getAccountAttemptsByPeriod($account, $startDate, $endDate);

                if (count($loginAttempts) >= $this->loginAttempts) {
                    if ($endDatePlusBanPeriod->format('Y-m-d H:i:s') > (new \DateTime())->format('Y-m-d H:i:s')) {
                        $interval = (new \DateTime())->diff($endDatePlusBanPeriod);

                        $content = $this->templatingEngine->render(
                            'security/login-blocked.html.twig',
                            [
                                'interval' => $interval->format("%I:%S")
                            ]
                        );

                        return new Response($content);
                    } else {
                        array_map(
                            function (LoginAttempt $loginAttempt) {
                                $this->entityManager->remove($loginAttempt);
                                $this->entityManager->flush();
                            },
                            $loginAttempts
                        );
                    }
                }
            }
        }
        return true;
    }
}