<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\BaseAccountType;
use App\Service\Manager\TenantAccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class AccountController
 * @package App\Controller
 */
class AccountController extends AbstractController
{
    /**
     * @Route("/profile", name="account_profile")
     * @param Request $request
     * @return Response
     */
    public function editProfile(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Account $account */
        $loggedUser = $this->getUser();
        $account = $em->getRepository(Account::class)->find($loggedUser->getId());

        $form = $this->createForm(BaseAccountType::class, $account, ['validation_groups' => ['edit', 'password']]);
        $password = $account->getPasswordHash();

        if ($request->isMethod('POST')) {
            if ($form->handleRequest($request)->isValid()) {
                if ($form->getData()->getPasswordHash()) {
                    $password = $passwordEncoder->encodePassword($account, $form->getData()->getPasswordHash());
                }
                $account->setPasswordHash($password);
                $em->persist($account);
                $em->flush();
            }
            $em->refresh($account);
        }

        return $this->render('account/profile.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("tenant/users/{id}/delete", name="tenant_account_delete", methods="GET|DELETE")
     * @param Request $request
     * @param TenantAccountManager $accountManager
     * @return Response
     */
    public function delete(Request $request, TenantAccountManager $accountManager): Response
    {
        return $accountManager->delete($request);
    }
}
