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
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/admin')]
class DashboardController extends AbstractDashboardController
{
    private const MENU = [
        'tables' => [
            ['Users', 'users', User::class],
            ['Groups', 'child', Group::class],
            ['Visits', 'suitcase', Visit::class],
            ['Topics', 'question', Topic::class],
            ['Schools', 'school', School::class],
            ['Locations', 'map-marker-alt', Location::class],
            ['Notes', 'sticky-note', Note::class],
            ['CalendarEvents', 'calendar', CalendarEvent::class],
        ],
        'routes' => [
            ['Arbetsfördelning', 'user-clock', 'tools_schedule_colleagues'],
            ['Bekräfta buss', 'tasks', 'tools_confirm_bus_orders'],
            ['Fördela besöksdatum', 'network-wired', 'tools_distribute_visits'],
            ['Satsvis redigering', 'layer-group', 'tools_batch_edit'],
            ['Lägg till grupper', 'plus-square', 'tools_add_groups'],
            ['Planera nästa termin', 'calendar-week', 'tools_plan_year'],
            ['Bussbeställning', 'bus', 'tools_order_bus'],
            ['Matbeställning', 'utensils', 'tools_order_food'],
            ['Mejlutskick', 'envelope', 'tools_send_mail'],
            ['Bussinställningar', 'bus-alt', 'tools_set_bus_settings'],
            ['Skolor besöksordning', 'sort-numeric-down', 'tools_order_schools'],
            ['Skapa API-keys', 'key', 'tools_create_api_keys'],
            ['Kolla upp användare', 'magnifying-glass' ,'tools_lookup_profile', ['mail' => '1']],
            ['Extra inställningar', 'cogs', 'tools_extra_settings'],
            ['Logg', 'th-list', 'tools_show_log'],
        ]
    ];


    public function __construct(
        protected RepoContainer     $rc,
        protected AdminUrlGenerator $routeBuilder,
        protected RequestStack $requestStack
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
            ->setTitle('Ndb Symfony')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        $extractCrud = fn($v) => MenuItem::linkToCrud($v[0], 'fas fa-' . $v[1], $v[2]);
        $extractRoute = fn($v) => MenuItem::linkToRoute($v[0], 'fas fa-' . $v[1], $v[2], $v[3] ?? []);

        return [
            MenuItem::linkToDashboard('Översikt', 'fa fa-home'),
            MenuItem::subMenu('Tabeller', 'fas fa-table')
                ->setSubItems(array_map($extractCrud, self::MENU['tables'])),
            MenuItem::subMenu('Verktyg', 'fas fa-tools')
                ->setSubItems(array_map($extractRoute, self::MENU['routes'])),
            MenuItem::subMenu('Skolsidor', 'fas fa-school')
                ->setSubItems($this->getSchoolsAsMenuItems()),

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
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(200)
            ->setDateFormat('YYYY-MM-dd')
            ->setDateTimeFormat('YYYY-MM-dd HH:mm');
    }

    private function getSchoolsAsMenuItems(): array
    {
        $schoolRepo = $this->rc->getSchoolRepo();

        return $schoolRepo->getActiveSchools()
            ->map(fn(School $s) => MenuItem::linkToRoute($s->getName(), '', 'school_overview', ['school' => $s->getId()]))
            ->toArray();
    }


}
