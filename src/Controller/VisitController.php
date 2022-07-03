<?php


namespace App\Controller;


use App\Entity\Visit;
use App\Repository\VisitRepository;
use App\Security\Role;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisitController extends AbstractController
{

    public function __construct(private RepoContainer $rc)
    {
    }

    #[Route('/visit/rate/{visit}', name: 'rate_visit')]
    #[IsGranted(Role::ACTIVE_USER)]
    public function rateVisit(?Visit $visit = null): Response
    {
        $visitRepo = $this->rc->getVisitRepo();
        if($visit === null){
            $visits = $visitRepo->findTodaysVisits();
            if(count($visits) > 1){
                return $this->render('rate_visit_overview.html.twig', ['visits' => $visits]);
            }
            if(count($visits) === 1){
                $visit = $visits[0];
            }
        }

        return $this->render('rate_visit.html.twig', ['visit' => $visit]);

    }








}