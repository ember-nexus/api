<?php

namespace App\Factory\Exception;

use App\Exception\Server500InternalServerErrorException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Server500InternalServerErrorExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $bag,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Returns an exception in the format of: "%s".
     */
    public function createFromTemplate(string $developmentDetail): Server500InternalServerErrorException
    {
        $message = 'Internal server error, see log.';
        if ('prod' !== $this->bag->get('kernel.environment')) {
            $message = $developmentDetail;
        } else {
            $this->logger->error($developmentDetail);
        }

        return new Server500InternalServerErrorException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '500',
                    'name' => 'internal-server-error',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $message
        );
    }
}
