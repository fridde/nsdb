<?php


namespace App\Message;

use App\Entity\User;
use App\Settings;
use App\Utils\ExtendedCollection;
use App\Utils\RepoContainer;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use App\Message\MessageDetails as MD;

class MessageBuilder
{
    private ?ExtendedCollection $usersWithFutureVisits;

    private ExtendedCollection $extraInfo;

    public function __construct(
        private RepoContainer   $rc,
        private Settings        $settings,
        private Environment     $twig,
        private MessageRecorder $mr,
        private MD              $md
    )
    {
        $this->usersWithFutureVisits = new ExtendedCollection();
        $this->extraInfo = ExtendedCollection::create();
    }

    public function collectAllMessages(): ExtendedCollection
    {
        return $this->collectMailsForVisitConfirmation()
            ->attach($this->collectMailsForIncompleteProfile())
            ->attach($this->collectMailsForNewVisitors())
            ->attach($this->collectMailsForAddedVisits());
    }

    private function collectMailsForVisitConfirmation(): ExtendedCollection
    {
        $type = MD::SUBJECT_VISIT_CONFIRMATION;
        $visitUntil = Carbon::today()->add(CarbonInterval::create(
            $this->settings->get('user_reminder.visit_confirmation_time'))
        );
        $createdBefore = $this->md->getImmunityTresholdDate();

        $users = $this->getUsersWithFutureVisits()
            ->filter(fn(User $u) => $u->getCreated()->lt($createdBefore))
            ->filter(fn(User $u) => $u->getUnconfirmedFutureVisitsUntil($visitUntil)->isNotEmpty());

        $extra = $users
            ->withKey(fn(User $u) => $u->getId())
            ->map(fn(User $u) => $u->getUnconfirmedFutureVisitsUntil($visitUntil));

        $this->extraInfo->set($type, $extra);

        return $this->removeAnnoyedUsers($users, $type)
            ->map(fn(User $u) => $this->createEMail($u, $type));
    }


    private function collectMailsForIncompleteProfile(): ExtendedCollection
    {
        $subject = MD::SUBJECT_INCOMPLETE_PROFILE;
        $createdBefore = $this->md->getImmunityTresholdDate();

        $users = $this->getUsersWithFutureVisits()
            ->filter(fn(User $u) => $u->getCreated()->lt($createdBefore))
            ->filter(fn(User $u) => !$u->hasMobil());

        return $this->removeAnnoyedUsers($users, $subject)
            ->map(fn(User $u) => $this->createEMail($u, $subject));
    }

    private function collectMailsForNewVisitors(): ExtendedCollection
    {
        $subject = MD::SUBJECT_FIRST_VISIT;
        $oneYearAgo = Carbon::today()->subYear();

        $users = $this->getUsersWithFutureVisits()
            ->filter(fn(User $u) => !$this->mr->userGotMessageAfter($u, $subject, $oneYearAgo));

        $extra = $users
            ->withKey(fn(User $u) => $u->getId())
            ->map(fn(User $u) => $u->getFutureVisits());

        $this->extraInfo->set($subject, $extra);

        return $users->map(fn(User $u) => $this->createEMail($u, $subject));
    }

    private function collectMailsForAddedVisits(): ExtendedCollection
    {
        $subject = MD::SUBJECT_VISITS_ADDED;

        $users = $this->getUsersWithFutureVisits()
            ->filter(fn(User $u) => $this->mr->userGotFirstVisitMessage($u))
            ->filter(fn(User $u) => $this->mr->userHasUnnotifiedFutureVisit($u));

        $extra = $users
            ->withKey(fn(User $u) => $u->getId())
            ->map(fn(User $u) => $this->mr->getUnnotifiedFutureVisits($u));

        $this->extraInfo->set($subject, $extra);

        return $users->map(fn(User $u) => $this->createEMail($u, $subject));
    }


    private function removeAnnoyedUsers(ExtendedCollection $users, string $subject): ExtendedCollection
    {
        $annoyedUserIds = $this->mr->getAnnoyedUserIds($subject);
        return $users
            ->filter(fn(User $u) => $annoyedUserIds->doesNotContain($u->getId()));
    }

    private function createEMail(User $user, string $subject): Email
    {
        $template = sprintf('mail/%s.html.twig', $subject);
        $subjectText = $this->md->translateSubject($subject);
        $extraInfo = $this->getExtraInfo($user, $subject);

        $this->mr->addAsTempRecord($user, $subject, $extraInfo);

        $vars = ['user' => $user];
        if ($extraInfo !== null) {
            $vars[MD::getVarNameForExtraInfo($subject)] = $extraInfo;
        }
        $mail = new Email();
        $mail->from($this->settings->get('addresses.admin'))
            ->to($user->getMail())
            ->subject($subjectText)
            ->html($this->twig->render($template, $vars));

        return $mail;
    }

    private function getExtraInfo(User $u, string $subject): ?array
    {
        $value = $this->extraInfo->get($subject)?->get($u->getId());
        if($value instanceof ExtendedCollection){
            return $value->toArray();
        }
        return $value;
    }

    private function getUsersWithFutureVisits(): ExtendedCollection
    {
        if ($this->usersWithFutureVisits->isEmpty()) {
            $this->usersWithFutureVisits = $this->rc->getUserRepo()->getActiveUsersWithFutureVisits();
        }

        return $this->usersWithFutureVisits;
    }
}