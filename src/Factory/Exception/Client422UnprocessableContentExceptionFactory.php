<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client422UnprocessableContentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client422UnprocessableContentExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromDetail(string $detail): Client422UnprocessableContentException
    {
        return new Client422UnprocessableContentException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '422',
                    'name' => 'unprocessable-content',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $detail
        );
    }

    public function createFromExpressionTimeout(int $timeoutInMilliseconds): Client422UnprocessableContentException
    {
        return $this->createFromDetail(sprintf(
            'Query execution timed out after %d ms.',
            $timeoutInMilliseconds
        ));
    }
}
