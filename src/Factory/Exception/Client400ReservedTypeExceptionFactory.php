<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client400ReservedTypeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400ReservedTypeExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromTemplate(string $type): Client400ReservedTypeException
    {
        return new Client400ReservedTypeException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '400',
                    'name' => 'reserved-type',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: sprintf(
                "The requested type '%s' is reserved and can not be used.",
                $type
            )
        );
    }
}
