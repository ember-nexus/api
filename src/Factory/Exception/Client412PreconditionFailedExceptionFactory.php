<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client412PreconditionFailedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client412PreconditionFailedExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function createFromTemplate(): Client412PreconditionFailedException
    {
        return new Client412PreconditionFailedException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '412',
                    'name' => 'precondition-failed',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
