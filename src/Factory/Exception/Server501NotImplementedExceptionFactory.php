<?php

namespace App\Factory\Exception;

use App\Exception\Server501NotImplementedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Server501NotImplementedExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function createFromTemplate(): Server501NotImplementedException
    {
        return new Server501NotImplementedException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '501',
                    'name' => 'not-implemented',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
