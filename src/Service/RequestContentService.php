<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client408RequestTimeoutExceptionFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns the body of requests which are read completely into memory, e.g. json bodies. The web server silently cuts
 * off request bodies which are sent too slowly, so a body which is shorter than announced is answered with 408
 * instead of being parsed, which would result in misleading parsing errors. Requests without `Content-Length`, e.g.
 * chunked ones, can not be checked.
 */
class RequestContentService
{
    public function __construct(
        private Client408RequestTimeoutExceptionFactory $client408RequestTimeoutExceptionFactory,
    ) {
    }

    public function getContent(Request $request): string
    {
        $content = $request->getContent();
        $announcedLength = $request->headers->get('Content-Length');
        if (null !== $announcedLength && ctype_digit($announcedLength) && strlen($content) < (int) $announcedLength) {
            throw $this->client408RequestTimeoutExceptionFactory->createFromIncompleteBody(strlen($content), (int) $announcedLength);
        }

        return $content;
    }
}
