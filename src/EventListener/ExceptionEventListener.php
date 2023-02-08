<?php

namespace App\EventListener;

use App\Exception\ExtendedException;
use App\Exception\ServerException;
use App\Response\ProblemJsonResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionEventListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
        private ParameterBagInterface $bag
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $originalException = $extendedException = $event->getThrowable();
        if (!($originalException instanceof ExtendedException)) {
            $extendedException = new ServerException();
        }

        $instance = $extendedException->getInstance();
        $instanceLink = $this->urlGenerator->generate(
            sprintf(
                'problem-%s',
                $instance
            )
        );

        // check if there are configured alternatives for the instance links
        if ($this->bag->has('problemInstanceLinks')) {
            if (array_key_exists($instance, $this->bag->get('problemInstanceLinks'))) {
                $instanceLink = $this->bag->get('problemInstanceLinks')[$instance];
            }
        }

        $data = [
            'type' => $extendedException->getType(),
            'title' => $extendedException->getTitle(),
            'status' => $extendedException->getStatus(),
            'instance' => $instanceLink,
            'detail' => $extendedException->getDetail(),
        ];

        if ($this->kernel->isDebug()) {
            $data['exception'] = [
                'message' => $originalException->getMessage(),
                'trace' => $originalException->getTrace(),
            ];
        }

        $event->setResponse(new ProblemJsonResponse(
            $data,
            $data['status']
        ));
        $event->stopPropagation();
    }
}
