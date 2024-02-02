<?php

namespace App\EventSystem\Kernel\EventListener;

use App\Service\RequestTimeService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestTimeTrackerListener
{

    public function __construct(private RequestTimeService $requestTimeService)
    {
    }

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $this->requestTimeService->setRequestStart(new \DateTime());
    }

}
