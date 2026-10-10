<?php

declare(strict_types=1);

namespace App\EventSystem\Exception\EventListener;

use App\Exception\ProblemJsonException;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Service\RequestIdService;
use App\Type\Response\ProblemJsonResponse;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionEventListener
{
    /**
     * Names of the members of every problem response; additional properties can not replace them.
     */
    private const array RESERVED_PROPERTY_NAMES = ['type', 'title', 'status', 'instance', 'detail', 'exception'];

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
        private LoggerInterface $logger,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory,
        private RequestIdService $requestIdService,
    ) {
    }

    /**
     * @SuppressWarnings("PHPMD.EmptyCatchBlock")
     */
    #[AsEventListener]
    public function onKernelException(ExceptionEvent $event): void
    {
        $originalException = $extendedException = $event->getThrowable();
        if (!($originalException instanceof ProblemJsonException)) {
            $extendedException = $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Other internal exception.', [
                'originalException' => $originalException,
            ]);
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

        $additionalProperties = array_diff_key(
            $extendedException->getAdditionalProperties(),
            array_flip(self::RESERVED_PROPERTY_NAMES)
        );

        $data = [
            'type' => $extendedException->getType(),
            'title' => $extendedException->getTitle(),
            'status' => $extendedException->getStatus(),
            // identifies this occurrence of the problem, the id is also part of the logs (`requestId` of the
            // application, `request_id` of the web server); see docker/Caddyfile for the errors of the web server
            'instance' => $instanceLink ?? sprintf('urn:uuid:%s', $this->requestIdService->getRequestId()->toString()),
            'detail' => $extendedException->getDetail(),
            ...$additionalProperties,
        ];

        if ('' === $data['detail']) {
            unset($data['detail']);
        }

        if ($this->kernel->isDebug()) {
            $data['exception'] = [
                'message' => $originalException->getMessage(),
                // getTrace() exposes the live call arguments of every frame, any of which (e.g. a non-backed enum
                // instance) can make json_encode() throw and take down the error response itself; the string
                // form carries the same debugging value (file/line/function per frame) without that risk
                'trace' => $originalException->getTraceAsString(),
            ];
        }
        // getMessage() is always empty (ProblemJsonException never forwards it to the parent Exception), getDetail()
        // carries the actual explanation, e.g. the received/announced byte counts of a 408 request timeout
        $this->logger->error(sprintf(
            '%s %s: %s',
            $extendedException->getType(),
            $extendedException->getTitle(),
            $extendedException->getDetail()
        ));

        $event->setResponse(new ProblemJsonResponse(
            $data,
            $data['status'],
            $extendedException->getHeaders()
        ));
        /**
         * @infection-ignore-all
         */
        $event->stopPropagation();
    }
}
