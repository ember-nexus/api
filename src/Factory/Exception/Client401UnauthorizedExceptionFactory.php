<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client401UnauthorizedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client401UnauthorizedExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function createFromTemplate(): Client401UnauthorizedException
    {
        return new Client401UnauthorizedException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '401',
                    'name' => 'unauthorized',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
