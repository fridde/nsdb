<?php

namespace App\Controller\Admin;

use App\Entity\CalendarEvent;
use App\Entity\Group;
use App\Entity\Location;
use App\Entity\Note;
use App\Entity\School;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\Visit;
use App\Repository\SchoolRepository;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/admin')]
class DashboardController extends AbstractDashboardController
{

    public function __construct(
        private RepoContainer $rc,
        private AdminUrlGenerator $routeBuilder
    )
    {
    }

    #[Route('/', name: 'admin_index')]
    public function index(): Response
    {
        $url = $this->routeBuilder
            ->setController(VisitCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Ndb Symfony');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Översikt', 'fa fa-home'),

            MenuItem::subMenu('Tabeller', 'fas fa-table')->setSubItems([
                MenuItem::linkToCrud('Users', 'fas fa-users', User::class),
                MenuItem::linkToCrud('Groups', 'fas fa-child', Group::class),
                MenuItem::linkToCrud('Visits', 'fas fa-suitcase', Visit::class),
                MenuItem::linkToCrud('Topics', 'fas fa-question', Topic::class),
                MenuItem::linkToCrud('Schools', 'fas fa-school', School::class),
                MenuItem::linkToCrud('Locations', 'fas fa-map-marker-alt', Location::class),
                MenuItem::linkToCrud('Notes', 'fas fa-sticky-note', Note::class),
                MenuItem::linkToCrud('CalendarEvents', 'fas fa-calendar', CalendarEvent::class),

            ]),
            MenuItem::subMenu('Verktyg', 'fas fa-tools')->setSubItems([
                MenuItem::linkToRoute('Arbetsfördelning', 'fas fa-user-clock', 'tools_schedule_colleagues'),
                MenuItem::linkToRoute('Bekräfta buss', 'fas fa-tasks', 'tools_confirm_bus_orders'),
                MenuItem::linkToRoute('Fördela besöksdatum', 'fas fa-network-wired', 'tools_distribute_visits'),
                MenuItem::linkToRoute('Planera nästa termin', 'fas fa-calendar-week', 'tools_plan_year'),
                MenuItem::linkToRoute('Bussbeställning', 'fas fa-bus', 'tools_order_bus'),
                MenuItem::linkToRoute('Matbeställning', 'fas fa-utensils', 'tools_order_food'),
                MenuItem::linkToRoute('Mejlutskick', 'fas fa-envelope', 'tools_send_mail'),
                MenuItem::linkToRoute('Bussinställningar', 'fas fa-bus-alt', 'tools_set_bus_settings'),
                MenuItem::linkToRoute('Skolor besöksordning', 'fas fa-sort-numeric-down', 'tools_order_schools'),
                MenuItem::linkToRoute('Skapa API-keys', 'fas fa-key', 'tools_create_api_keys'),
                MenuItem::linkToRoute('Logg', 'fas fa-th-list', 'tools_show_log'),
            ]),
            MenuItem::subMenu('Skolsidor', 'fas fa-school')->setSubItems($this->getSchoolsAsMenuItems()),

            MenuItem::section(),
            MenuItem::linkToLogout('Logout', 'fas fa-sign-out-alt')
        ];
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addWebpackEncoreEntry('admin');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setPaginatorPageSize(200)
            ->setDateFormat('YYYY-MM-dd')
            ->setDateTimeFormat('YYYY-MM-dd HH:mm');
    }

    private function getSchoolsAsMenuItems(): array
    {
        $schoolRepo = $this->rc->getSchoolRepo();

        return $schoolRepo->getActiveSchools()
            ->map(fn(School $s) =>
            MenuItem::linkToRoute($s->getName(), '', 'school_overview', ['school' => $s->getId()]))
        ->toArray();
    }


}
