<?php

namespace App\Controller\Admin\Tool;

use App\Entity\Group;
use App\Entity\Visit;
use App\Utils\RepoContainer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class DirectMessageController extends AbstractController
{
    public const BASE_URL = 'https://teams.microsoft.com/l/chat/0/0?';

    public function __construct(private RepoContainer $rc)
    {
    }

    #[Route('/admin/create-dm/confirmation/{visit}', name: 'create-dm-for-confirmation')]
    public function createTeamsMessageForConfirmation(Visit $visit): Response
    {
        $data = $this->getDataForSiblingVisits($visit);
        $data['message_type'] = 'confirmation';

        $message = $this->renderView('admin/teams_message.twig', $data);
        $url = $this->buildUrlForTeamsMessage($data['addresses'], $message, 'Bekräftelse');
        return new RedirectResponse($url);
    }

    #[Route('/admin/create-dm/welcome/{visit}', name: 'create-dm-for-welcome')]
    public function createTeamsMessageForWelcome(Visit $visit): Response
    {
        $data = $this->getDataForSiblingVisits($visit);
        $data['message_type'] = 'welcome';

        $message = $this->renderView('admin/teams_message.twig', $data);
        $url = $this->buildUrlForTeamsMessage($data['addresses'], $message, 'Nästa temadag med Naturskolan');
        return new RedirectResponse($url);
    }

    private function getDataForSiblingVisits(Visit $visit): array
    {
        $visits = $this->rc->getVisitRepo()->getSiblingVisits($visit);
        $data['addresses'] = $visits
            ->map(fn(Visit $v) => $v->getGroup()?->getUser()?->getMail())
            ->unique()
            ->toArray();

        $data['visits'] = $visits;
        $data['dates'] = $visits->map(fn(Visit $v) => $v->getDate())->unique();
        $data['topic'] = $visit->getTopic();

        return $data;

    }

    private function buildUrlForTeamsMessage(
        array $addresses,
        ?string $message = null,
        ?string $topic = null
    ): string
    {
        $parts = ['users=' . implode(',', $addresses)];
        $parts[] = 'topicName=' . urlencode($topic ?? '');
        $parts[] = 'message=' . urlencode($message ?? '');

        return self::BASE_URL . implode('&', $parts);
    }
}

// https://teams.microsoft.com/l/chat/0/0?users=joe@contoso.com,bob@contoso.com&topicName=Prep%20For%20Meeting%20Tomorrow&message=Hi%20folks%2C%20kicking%20off%20a%20chat%20about%20our%20meeting%20tomorrow