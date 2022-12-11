<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\School;
use App\Entity\User;
use App\Enums\Segment;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Security\Role;
use App\Settings;
use App\Utils\RepoContainer;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class SchoolPageController extends AbstractController
{

    public function __construct(
        private Settings $settings,
        private RepoContainer $rc
    )
    {
    }

    #[Route(
        '/skola/{school}',
        name: 'school_overview',
        methods: ['GET']
    )]
    #[
        IsGranted(Role::ACTIVE_USER),
        IsGranted('edit', subject: 'school')
    ]
    #[Template('school_page.html.twig')]
    public function schoolOverview(School $school, Request $request): array
    {
        $data['requested_school'] = $school;

        $interval = CarbonInterval::create($this->settings->get('user_reminder.visit_confirmation_visible'));
        $data['latest_confirmable_date'] = Carbon::today()->add($interval)->toDateString();
        $data['today'] = Carbon::today()->toDateString();

        $groups = $this->rc->getGroupRepo()
            ->isActive()
            ->hasSchool($school)
            ->addOrder('Name')
            ->getMatching();
        $segments = array_fill_keys(Segment::getValues(), []);
        foreach ($groups as $group) {
            /** @var Group $group */
            $segments[$group->getSegment()->value][] = $group;
        }
        $data['segments'] = array_filter($segments);
        $data['segment_labels'] = Segment::getLabels();

        $ignoreApproval = (array)json_decode($request->cookies->get('ignore_approval', '[]'));

        $users = $this->rc->getUserRepo()
            ->isActive()
            ->hasSchool($school)
            ->addMultipleOrders(['FirstName', 'LastName'])
            ->getMatching();

        $data['users'] = $users;
        $data['pending_users'] = $users->filter(
            fn(User $u) => $u->isPending() && !in_array($u->getId(), $ignoreApproval, false)
        );
        $data['ignore_approval'] = json_encode($ignoreApproval);

        return $data;
    }


}
