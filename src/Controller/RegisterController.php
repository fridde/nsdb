<?php


namespace App\Controller;

use App\Entity\School;
use App\Repository\SchoolRepository;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegisterController extends AbstractController
{

    public function __construct(private RequestStack $requestStack, private RepoContainer $rc)
    {
    }


    #[Route('/register', name: 'register')]
    #[Template('register.html.twig')]
    public function registerForm(): array
    {
        $data['user'] = json_decode($this->requestStack->getSession()->get('user_data'), true);
        $data['schools'] = $this->rc->getSchoolRepo()->getActiveSchools();

        return $data;
    }

    #[Route('/register/pending', name: 'register_pending')]
    #[Template('register_pending.html.twig')]
    public function registerPendingInfo(): array
    {
        $data['user'] = json_decode($this->requestStack->getSession()->get('user_data'), true);
        $data['schools'] = $this->rc->getSchoolRepo()->getActiveSchools();

        return $data;
    }



}