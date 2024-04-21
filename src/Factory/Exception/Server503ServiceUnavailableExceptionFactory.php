<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Server503ServiceUnavailableException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Server503ServiceUnavailableExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $bag
    ) {
    }

    /**
     * Returns an exception in the format of: "Service '%s' is currently unavailable.".
     */
    public function createFromTemplate(string $service): Server503ServiceUnavailableException
    {
        $message = 'The service itself or an internal component is currently unavailable.';
        if ('prod' !== $this->bag->get('kernel.environment')) {
            $message = sprintf(
                "Service '%s' is currently unavailable.",
                $service
            );
        }

        return new Server503ServiceUnavailableException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '503',
                    'name' => 'service-unavailable',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $message
        );
    }
}
