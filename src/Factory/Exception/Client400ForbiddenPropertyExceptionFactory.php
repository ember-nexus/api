<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client400ForbiddenPropertyException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400ForbiddenPropertyExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Returns an exception in the format of "Endpoint does not accept setting the property '%s' in the request.".
     */
    public function createFromTemplate(string $propertyName): Client400ForbiddenPropertyException
    {
        return new Client400ForbiddenPropertyException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '400',
                    'name' => 'forbidden-property',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: sprintf(
                "Endpoint does not accept setting the property '%s' in the request.",
                $propertyName
            )
        );
    }
}
