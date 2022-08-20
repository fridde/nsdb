<?php


namespace App\Controller;


use App\Controller\Admin\DashboardController;
use App\Entity\User;
use App\Security\Role;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    #[Route('/', name: 'index')]
    #[IsGranted(Role::USER)]
    public function enter(): Response
    {
        $user = $this->getUser();
        if($user instanceof User){
            if($user->isPending()){
                // TODO: this should render an info page that the registration is still ongoing
                dump('This should show the *pending user* page');
                return $this->render('base.html.twig');
            }
            if($this->isGranted(Role::SUPER_ADMIN)){
                return $this->forward(DashboardController::class . '::index');
            }
            return $this->forward(SchoolPageController::class . '::schoolOverview',
                ['school' => $user->getSchool()]
            );
        }

        // TODO: this page should never be reached since any unauthorized user should either be sent to azure-authenticator or "register"
        dump('This this page should never be reached.');
        return $this->render('base.html.twig');

    }


    #[Route('/logout', name:'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Nothing in this method will be executed, it's just there to satisfy the router
        // ALl logic is executed in LogoutSubscriber
    }



}