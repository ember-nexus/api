<?php

namespace App\EventSystem\Exception\EventListener;

use App\Exception\ProblemJsonException;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Response\ProblemJsonResponse;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionEventListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
        private ParameterBagInterface $bag,
        private LoggerInterface $logger,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory
    ) {
    }

    /**
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $this->logger->error($event->getThrowable());
        $originalException = $extendedException = $event->getThrowable();
        if (!($originalException instanceof ProblemJsonException)) {
            $extendedException = $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Other internal exception.');
        }
        /**
         * @var ProblemJsonException $extendedException
         */
        $instance = $extendedException->getInstance();
        $instanceLink = null;
        try {
            $instanceLink = $this->urlGenerator->generate(
                sprintf(
                    'problem-%s',
                    $instance ?? 'unknown'
                )
            );
        } catch (Exception $e) {
        }

        // check if there are configured alternatives for the instance links
        if ($this->bag->has('problemInstanceLinks') && $instance) {
            $problemInstanceLinksConfig = $this->bag->get('problemInstanceLinks');
            if (is_array($problemInstanceLinksConfig)) {
                if (array_key_exists($instance, $problemInstanceLinksConfig)) {
                    $instanceLink = $problemInstanceLinksConfig[$instance];
                }
            }
        }

        $data = [
            'type' => $extendedException->getType(),
            'title' => $extendedException->getTitle(),
            'status' => $extendedException->getStatus(),
            'instance' => $instanceLink,
            'detail' => $extendedException->getDetail(),
        ];

        if (null === $instanceLink) {
            unset($data['instance']);
        }

        if ('' === $data['detail']) {
            unset($data['detail']);
        }

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
