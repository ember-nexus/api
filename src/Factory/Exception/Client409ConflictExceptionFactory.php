<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client409ConflictException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client409ConflictExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $additionalDetails
     */
    public function createFromDetail(string $detail, array $additionalDetails = []): Client409ConflictException
    {
        return new Client409ConflictException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '409',
                    'name' => 'conflict',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $detail,
            additionalDetails: $additionalDetails
        );
    }
}
