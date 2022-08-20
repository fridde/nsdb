<?php


namespace App\Controller;


use App\Entity\Group;
use App\Entity\Note;
use App\Entity\Visit;
use App\Repository\VisitRepository;
use App\Security\Role;
use App\Utils\ExtendedCollection;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisitController extends AbstractController
{

    public function __construct(private RepoContainer $rc)
    {
    }


    #[Route('/visit/rate/{visit}', name: 'rate_visit_view', priority: 3)] // priority -> the word "rate" in the url should not be considered an id
    #[IsGranted(Role::ACTIVE_USER)]
    public function rateVisitView(?Visit $visit = null): Response
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



    #[Route('/visit/confirm/{visit}', name: 'confirm_visit')]
    #[IsGranted('confirm', subject: 'visit')]
    public function confirmVisit(Visit $visit): Response
    {
        $visit->setConfirmed(true);
        $this->rc->getEntityManager()->flush();

        $response = $this->forward(SchoolPageController::class . '::schoolOverview', [
            'school' => $visit->getGroup()?->getSchool()
        ]);

        $text =  sprintf(
            "Tack! Besöket på %s med klass %s från %s har blivit bekräftad.",
            $visit->getDateString(),
            $visit->getGroup()?->getName(),
            $visit->getGroup()?->getSchool()->getName()
        );

        $this->addFlash('success', $text);

        return $response;
    }

    #[Route('/visit/note/{visit}', name: 'note_for_visit')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function addNoteForVisit(Visit $visit): Response
    {
        $data['group'] = $group = $visit->getGroup();
        $data['visit'] = $visit;

        if($group instanceof Group){
            $notes = $group->getNotes()->toArray();
            $data['notes'] = $group->getNotes()->reverse();
            $data['user_note'] = $data['notes']
                ->filter(fn(Note $n) => $n->getVisit() === $visit)
                ->filter(fn(Note $n) => $n->getUser()->getUserIdentifier() === $this->getUser()?->getUserIdentifier())
                ->first();
        }

        return $this->render('admin/edit_notes.html.twig', $data);
    }

    // This has to come last
    #[Route('/visit/{visit}', name: 'visit_overview')]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function visitOverview(?Visit $visit = null): Response
    {
        $visitRepo = $this->rc->getVisitRepo();
        $visits = ($visit === null ? $visitRepo->findTodaysVisits() : [$visit]);

        return $this->render('admin/visit_overview.html.twig', ['visits' => $visits]);
    }









}