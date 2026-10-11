<?php

namespace App\Security;

trait MailAddressAnalyzer
{
    const string GOOGLE = 'google';
    const string MICROSOFT = 'microsoft';


    public function determineOAuthScheme(string $mailAddress): ?string {
        $domain = explode('@', strtolower(trim($mailAddress)))[1];

        return match ($domain) {
            'edu.sigtuna.se', 'sigtuna.se', 'varingaskolan.se' => self::MICROSOFT,
            'engelska.se' => self::GOOGLE,
            default => null,
        };

    }

}