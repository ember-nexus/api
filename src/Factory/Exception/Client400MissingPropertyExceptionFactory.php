<?php

namespace App\Factory\Exception;

use App\Exception\Client400MissingPropertyException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400MissingPropertyExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Returns an exception in the format of: "Endpoint requires that the request contains property '%s' to be set to %s.".
     */
    public function createFromTemplate(string $propertyName, mixed $propertyValue): Client400MissingPropertyException
    {
        return new Client400MissingPropertyException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '400',
                    'name' => 'missing-property',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: sprintf(
                "Endpoint requires that the request contains property '%s' to be set to %s.",
                $propertyName,
                $propertyValue
            )
        );
    }
}
