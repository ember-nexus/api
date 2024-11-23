<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client429TooManyRequestsException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client429TooManyRequestsExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromTemplate(): Client429TooManyRequestsException
    {
        return new Client429TooManyRequestsException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '429',
                    'name' => 'too-many-requests',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
