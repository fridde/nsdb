<?php

namespace App\EventSubscriber;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $testDate = $event->getRequest()->query->get('testdate');

        if ($_SERVER['APP_DEBUG']) {
            $GLOBALS['LOGGER'] = $this->logger;
            if ($testDate !== null) {
                Carbon::setTestNow($testDate);
            }
        }
    }

    public
    static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
