<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client410GoneException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client410GoneExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromTemplate(): Client410GoneException
    {
        return new Client410GoneException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '410',
                    'name' => 'gone',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
