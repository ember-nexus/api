<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client400BadGrammarException;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400BadGrammarExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromDetail(string $detail, ?Exception $exception): Client400BadGrammarException
    {
        return new Client400BadGrammarException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '400',
                    'name' => 'bad-grammar',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $detail,
            previous: $exception
        );
    }
}
