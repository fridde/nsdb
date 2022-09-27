<?php

namespace App\Twig;

use App\Entity\User;
use App\Entity\Visit;
use App\Security\AuthenticationUtils;
use App\Settings;
use Twig\Extension\RuntimeExtensionInterface;

class AppRuntime implements RuntimeExtensionInterface
{

    public function __construct(
        private AuthenticationUtils $auth,
        private Settings $settings
    )
    {
    }

    public function creatUrlKeyStringForUser(User $user): string
    {
        return $this->auth->createUrlKeyStringForUser($user);
    }

    public function getSnippet(...$keys): ?string
    {
        array_unshift($keys, 'snippets');

        return $this->settings->getUsingKeys(...$keys);
    }

    public function getSetting(...$keys): mixed
    {
        return $this->settings->getUsingKeys(...$keys);
    }

    public function getGoogleMapsUrl(string $coordinates): string
    {
        return 'https://www.google.com/maps/place/' . rawurlencode($coordinates);
    }



}