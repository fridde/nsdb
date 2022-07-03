<?php

namespace App\EventSubscriber;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use TheNetworg\OAuth2\Client\Provider\Azure;

class LogoutSubscriber implements EventSubscriberInterface
{
    private ClientRegistry $clientRegistry;
    private RequestStack $requestStack;
    private UrlGeneratorInterface $router;

    public function __construct(
        ClientRegistry $clientRegistry,
        RequestStack $requestStack,
        UrlGeneratorInterface $router
    )
    {
        $this->clientRegistry = $clientRegistry;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutEvent',
        ];
    }

    public function onLogoutEvent(LogoutEvent $event): void
    {
        /** @var Azure $provider  */
        $provider = $this->clientRegistry->getClient('azure')->getOAuth2Provider();
        $url = $this->router->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $logoutUrl = $provider->getLogoutUrl($url);

        /** @var RedirectResponse $response  */
        $response = $event->getResponse();
        $response->headers->clearCookie('key');
        $response->setTargetUrl($logoutUrl);
        $this->requestStack->getSession()->clear();
    }


}
