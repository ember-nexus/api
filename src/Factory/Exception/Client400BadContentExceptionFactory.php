<?php

namespace App\Factory\Exception;

use App\Exception\Client400BadContentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400BadContentExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Returns an exception in the format of "Endpoint expects property '%s' to be %s, got '%s'.".
     */
    public function createFromTemplate(string $propertyName, string $expectedContent, mixed $realContent): Client400BadContentException
    {
        return new Client400BadContentException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '400',
                    'name' => 'bad-content',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: sprintf(
                "Endpoint expects property '%s' to be %s, got '%s'.",
                $propertyName,
                $expectedContent,
                $realContent
            )
        );
    }
}
