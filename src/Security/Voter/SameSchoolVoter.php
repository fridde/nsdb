<?php

namespace App\Security\Voter;

use App\Entity\Group;
use App\Entity\School;
use App\Entity\User;
use App\Entity\Visit;
use App\Security\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class SameSchoolVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const CREATE = 'create';
    public const DELETE = 'delete';
    public const CONFIRM = 'confirm';

    private const ACTIONS = [self::VIEW, self::EDIT, self::CREATE, self::DELETE, self::CONFIRM];

    private const ENTITIES = [
        School::class,
        User::class,
        Visit::class,
        Group::class
    ];

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {

        return in_array($attribute, self::ACTIONS, true) && $this->hasRightClass($subject) !== null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if($this->security->isGranted(Role::SUPER_ADMIN)){
            return true;
        }

        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
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
