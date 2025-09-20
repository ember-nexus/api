<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Server500InternalServerErrorException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

class Server500InternalServerErrorExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $bag,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Returns an exception in the format of: "%s".
     *
     * @param mixed[] $context
     */
    public function createFromTemplate(string $developmentDetail, array $context = [], ?Throwable $previous = null): Server500InternalServerErrorException
    {
        $this->logger->error($developmentDetail, $context);
        $message = 'Internal server error, see log.';
        if ('prod' !== $this->bag->get('kernel.environment')) {
            $message = $developmentDetail;
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
            detail: $message,
            previous: $previous
        );
    }
}
