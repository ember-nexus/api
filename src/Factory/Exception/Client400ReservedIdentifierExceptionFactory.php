<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client400ReservedIdentifierException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400ReservedIdentifierExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Returns an exception in the format of: "The requested identifier '%s' is reserved and can not be used.".
     */
    public function createFromTemplate(string $identifier): Client400ReservedIdentifierException
    {
        return new Client400ReservedIdentifierException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '400',
                    'name' => 'reserved-identifier',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: sprintf(
                "The requested identifier '%s' is reserved and can not be used.",
                $identifier
            )
        );
    }
}
