<?php

namespace App\Security\Voter;


use App\Security\Key\ApiKeyManager;
use App\Security\Key\Key;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class ApiKeyVoter extends Voter
{

    public function __construct(private ApiKeyManager $akm)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, Key::getAllTypes(), true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $keyCode = $this->akm->getKeyCodeFromRequest();
        $key = $this->akm->createKeyFromGivenString($keyCode);

        return ($key->isType($attribute) && $this->akm->isValidKey($key));
    }
}
