<?php

namespace App\Security\Voter;

use App\Entity\Group;
use App\Entity\School;
use App\Entity\User;
use App\Entity\Visit;
use App\Security\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class SameSchoolVoter extends Voter
{
    public const string VIEW = 'view';
    public const string EDIT = 'edit';
    public const string CREATE = 'create';
    public const string DELETE = 'delete';
    public const string CONFIRM = 'confirm';

    private const array ACTIONS = [self::VIEW, self::EDIT, self::CREATE, self::DELETE, self::CONFIRM];

    private const array ENTITIES = [
        School::class,
        User::class,
        Visit::class,
        Group::class
    ];

    public function __construct(
        private readonly Security $security
    )
    {
    }


    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS, true) && $this->hasRightClass($subject) !== null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, Vote|null $vote = null): bool
    {
        if($this->security->isGranted(Role::SUPER_ADMIN)){
            return true;
        }

        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        if(!$user->isActive()){
            return false;
        }

        if(!$this->hasRightSchool($user, $subject)){
            return false;
        }

        return match([$attribute, get_class($subject)]){
            [self::DELETE, User::class] => $this->security->isGranted(Role::SCHOOL_ADMIN),

            default => true
        };

    }

    private function hasRightSchool(User $user, $subject): ?bool
    {
        $userSchool = $user->getSchool();

        return match(true){
            $subject instanceof School => $subject->equals($userSchool),
            $subject instanceof User => $subject->hasSameSchoolAs($user),
            $subject instanceof Visit => $subject->getGroup()?->getSchool()->equals($userSchool),
            $subject instanceof Group => $subject->getSchool()->equals($userSchool)
        };
    }

    private function hasRightClass($subject): bool
    {
        if(!is_object($subject)){
            return false;
        }

        return in_array(get_class($subject), self::ENTITIES, true);
    }
}
