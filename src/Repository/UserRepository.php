<?php

namespace App\Repository;

use App\Entity\School;
use App\Entity\User;
use App\Security\Role;
use App\Utils\Attributes\FilterMethod;
use App\Utils\ExtendedCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    use Filterable;

    #[FilterMethod('school')]
    public function hasSchool(School $school): self
    {
        return $this->addAndFilter('School', $school);
    }

    public function orderedBy(array $fields = ['FirstName', 'LastName']): self
    {
        $orders = array_fill_keys($fields, Criteria::ASC);

        return $this->addMultipleOrders($orders);
    }

    public function findOneByMail($mail): ?User
    {
        return $this->findOneBy(['Mail' => $mail]);
    }

    public function getColleagues(): ExtendedCollection
    {
        return $this
            ->isActive()
            ->hasSchool($this->getEntityManager()->find(School::class, School::NATURSKOLAN))
            ->orderedBy(['FirstName', 'LastName'])
            ->getMatching();
    }

    public function getActiveUsersWithFutureVisits(): ExtendedCollection
    {
        return $this->isActive()->getMatching()
            ->filter(fn(User $u) => $u->hasGroupWithFutureVisit());
    }

    public function getSchoolAdmins(): ExtendedCollection
    {
        return $this->isActive()->getMatching()
            ->filter(fn(User $u) => $u->hasRole(Role::SCHOOL_ADMIN));
    }

    public function getClosestMatch(ExtendedCollection $allUsers, string $completeName): ?User
    {
        $shortest = 999;
        $closest = $allUsers->first();

        foreach($allUsers as $user){
            /** @var User $user  */
            $distance = levenshtein($completeName, $user->getFullName());
            if($distance < $shortest){
                $closest = $user;
                $shortest = $distance;
            }
            if($shortest === 0){
                break;
            }
        }
        return $closest;
    }






}