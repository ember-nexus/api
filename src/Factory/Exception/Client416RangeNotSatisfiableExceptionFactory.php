<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client416RangeNotSatisfiableException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client416RangeNotSatisfiableExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $additionalProperties
     */
    public function createFromDetail(string $detail, array $additionalProperties = [], ?int $totalLength = null): Client416RangeNotSatisfiableException
    {
        $exception = new Client416RangeNotSatisfiableException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '416',
                    'name' => 'range-not-satisfiable',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $detail,
            additionalProperties: $additionalProperties
        );
        if (null !== $totalLength) {
            // RFC 9110, 15.5.17: unsatisfied-range, tells the client the current length of the selected representation
            $exception->setHeaders(['Content-Range' => sprintf('bytes */%d', $totalLength)]);
        }

        return $exception;
    }
}
