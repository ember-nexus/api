<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client408RequestTimeoutException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client408RequestTimeoutExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromDetail(string $detail): Client408RequestTimeoutException
    {
        return new Client408RequestTimeoutException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '408',
                    'name' => 'request-timeout',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $detail
        );
    }

    /**
     * The server cuts off slow request bodies silently, so a shorter-than-announced body is the only sign of it.
     */
    public function createFromIncompleteBody(int $receivedLength, int $announcedLength): Client408RequestTimeoutException
    {
        return $this->createFromDetail(sprintf(
            'The request body is incomplete, received %d of %d bytes announced by the Content-Length header. The connection was interrupted or the request body was not sent within the time limit of the server.',
            $receivedLength,
            $announcedLength
        ));
    }
}
