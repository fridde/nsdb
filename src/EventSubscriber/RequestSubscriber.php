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
        $activateLogger = $event->getRequest()->query->get('logger');

        if($activateLogger){
            $GLOBALS['LOGGER'] = $this->logger;
        }

        if ($_SERVER['APP_DEBUG']) {
            $testDate = $event->getRequest()->query->get('testdate');
            if ($testDate !== null) {
                Carbon::setTestNow($testDate);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}


