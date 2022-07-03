<?php

namespace App\EventSubscriber;

use App\Security\Key\ApiKeyManager;
use App\Security\Key\Key;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{

    public function __construct(private Environment $twig, private ApiKeyManager $akm)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }

    public function onKernelController(): void
    {
        $key = $this->akm->createKeyFromValues(Key::TYPE_ANON);
        $this->twig->addGlobal('API_KEY', $this->akm->createCodeStringForKey($key));
    }


}
