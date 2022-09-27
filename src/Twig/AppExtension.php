<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{

    public function getFilters(): array
    {
        return [
            new TwigFilter('url_key', [AppRuntime::class, 'creatUrlKeyStringForUser']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('snippet', [AppRuntime::class, 'getSnippet']),
            new TwigFunction('get_setting', [AppRuntime::class, 'getSetting']),
            new TwigFunction('gmaps_url', [AppRuntime::class, 'getGoogleMapsUrl']),
        ];
    }

}