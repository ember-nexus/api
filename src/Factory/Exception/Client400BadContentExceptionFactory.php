<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client400BadContentException;
use Safe\Exceptions\JsonException;
use Stringable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

class Client400BadContentExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createFromDetail(string $detail, ?Throwable $previous = null): Client400BadContentException
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
            detail: $detail,
            previous: $previous
        );
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    private function getContentSummary(mixed $content): string
    {
        if (is_string($content)) {
            if (strlen($content) > 250) {
                return sprintf(
                    "string '%s' (truncated)",
                    substr($content, 0, 250)
                );
            }

            return sprintf(
                "string '%s'",
                $content
            );
        }
        if (is_null($content)) {
            return 'null';
        }
        if (is_bool($content)) {
            return $content ? 'bool true' : 'bool false';
        }
        if (is_int($content)) {
            return sprintf(
                'int %d',
                $content
            );
        }
        if (is_float($content)) {
            return sprintf(
                'float %g',
                $content
            );
        }
        if (is_array($content)) {
            $count = count(array_values($content));
            if (0 === $count) {
                return 'array with no elements';
            }
            if (1 === $count) {
                return 'array with one element';
            }

            return sprintf(
                'array with %d elements',
                $count
            );
        }
        if ($content instanceof Stringable) {
            return sprintf(
                "'%s' of type %s",
                substr((string) $content, 0, 250),
                get_debug_type($content)
            );
        }

        return sprintf(
            'type %s',
            get_debug_type($content)
        );
    }

    /**
     * Returns an exception in the format of "Endpoint expects property '%s' to be %s, got '%s'.".
     */
    public function createFromTemplate(string $propertyName, string $expectedContent, mixed $realContent, ?Throwable $previous = null): Client400BadContentException
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
                "Endpoint expects property '%s' to be %s, got %s.",
                $propertyName,
                $expectedContent,
                $this->getContentSummary($realContent)
            ),
            previous: $previous
        );
    }

    public function createFromJsonException(JsonException $jsonException): Client400BadContentException
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
                'Unable to parse request as JSON. %s.',
                $jsonException->getMessage()
            ),
            previous: $jsonException
        );
    }
}
