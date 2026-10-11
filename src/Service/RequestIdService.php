<?php

declare(strict_types=1);

namespace App\Service;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The web server sets the request header `X-Request-Id` for every request (docker/Caddyfile), which is also part of
 * its access log and, as `instance` (`urn:uuid:<id>`), of every problem json response, so that a client can find the
 * log lines of its request. Without the header, e.g. on the console, a new id is generated.
 */
class RequestIdService
{
    public const string HEADER_NAME = 'X-Request-Id';

    private ?UuidInterface $requestId = null;

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function getRequestId(): UuidInterface
    {
        if (null === $this->requestId) {
            $this->requestId = $this->getRequestIdFromHeader() ?? Uuid::uuid4();
        }

        return $this->requestId;
    }

    private function getRequestIdFromHeader(): ?UuidInterface
    {
        $headerValue = $this->requestStack->getMainRequest()?->headers->get(self::HEADER_NAME);
        if (null === $headerValue || !Uuid::isValid($headerValue)) {
            return null;
        }

        return Uuid::fromString($headerValue);
    }
}
