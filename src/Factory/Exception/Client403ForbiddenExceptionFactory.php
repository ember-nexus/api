<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client403ForbiddenException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client403ForbiddenExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromTemplate(?string $detail = null): Client403ForbiddenException
    {
        return new Client403ForbiddenException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '403',
                    'name' => 'forbidden',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $detail ?? 'Requested endpoint, element or action is forbidden.'
        );
    }
}
