<?php

namespace App\Security;

class Role
{
    public const USER = 'ROLE_USER';
    public const ACTIVE_USER = 'ROLE_ACTIVE_USER';
    public const SCHOOL_ADMIN = 'ROLE_SCHOOL_ADMIN';
    public const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ORDERED = [  // NEVER CHANGE THIS ORDER
        self::USER,
        self::ACTIVE_USER,
        self::SCHOOL_ADMIN,
        self::SUPER_ADMIN
    ];

    public static function addRole(string $newRole, array $currentRoles): array
    {
        return self::addRoles([$newRole], $currentRoles);
    }

    public static function addRoles(array $newRoles, array $currentRoles): array
    {
        self::verifyAll($newRoles);
        $roles = [...$currentRoles, ...$newRoles];

        return self::orderRoles($roles);
    }

    public static function removeRole(string $role, array $currentRoles): array
    {
        return self::removeRoles([$role], $currentRoles);
    }

    public static function removeRoles(array $rolesToRemove, array $currentRoles): array
    {
        self::verifyAll($rolesToRemove);
        $currentRoles = array_diff($currentRoles, $rolesToRemove);

        return self::orderRoles($currentRoles);
    }

    public static function isAtLeast(string $roleToCheck, string $roleToCompare): bool
    {
        $a = array_search($roleToCheck, self::ORDERED, true);
        $b = array_search($roleToCompare, self::ORDERED, true);

        return $a >= $b ;
    }

    public static function orderRoles(array $roles): array
    {
        return array_values(array_intersect(self::ORDERED, $roles));
    }

    public static function getHighestRole(array $roles): ?string
    {
        if(count($roles) === 0){
            return null;
        }
        $ordered = self::orderRoles($roles);

        return end($ordered);
    }

    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::ORDERED, true);
    }

    public static function verify(string $role): void
    {
        if(!self::isValidRole($role)){
            throw new \InvalidArgumentException('The role "' . $role . '" is not a valid role.');
        }
    }

    public static function verifyAll(array $roles): void
    {
        array_walk($roles, fn(string $role) => self::verify($role));
    }
}