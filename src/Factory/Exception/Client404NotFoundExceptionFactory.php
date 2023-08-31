<?php

namespace App\Factory\Exception;

use App\Exception\Client404NotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client404NotFoundExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function createFromTemplate(): Client404NotFoundException
    {
        return new Client404NotFoundException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '404',
                    'name' => 'not-found',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
