<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\UserService;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class FacilityController
 * @package Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(
        UserService $userService,
        Security $security,
        RouterInterface $router,
        TokenStorageInterface $tokenStorage
    ) {
       return $userService->redirectUser($security, $router,  $tokenStorage);
    }
}