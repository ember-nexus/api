<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client405MethodNotAllowedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client405MethodNotAllowedExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromTemplate(): Client405MethodNotAllowedException
    {
        return new Client405MethodNotAllowedException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '405',
                    'name' => 'method-not-allowed',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
