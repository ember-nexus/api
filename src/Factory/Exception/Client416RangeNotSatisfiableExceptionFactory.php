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
     * @param array<string, mixed> $additionalDetails
     */
    public function createFromDetail(string $detail, array $additionalDetails = []): Client416RangeNotSatisfiableException
    {
        return new Client416RangeNotSatisfiableException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '416',
                    'name' => 'range-not-satisfiable',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $detail,
            additionalDetails: $additionalDetails
        );
    }
}
