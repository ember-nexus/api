<?php

namespace App\Tests\ExampleGeneration;

use Psr\Http\Message\ResponseInterface;

abstract class BaseRequestTestCase extends \App\Tests\FeatureTests\BaseRequestTestCase
{
    private const IGNORED_HEADERS = ['Date', 'Location'];

    private const REMOVED_HEADERS = ['X-Debug-Token', 'X-Debug-Token-Link', 'Set-Cookie'];

    public function getHeadersFromRequest(ResponseInterface $response): string
    {
        $cleanedHeaders = [];
        foreach ($response->getHeaders() as $key => $values) {
            if (in_array($key, self::REMOVED_HEADERS)) {
                continue;
            }
            foreach ($values as $value) {
                $cleanedHeaders[] = sprintf(
                    '%s: %s',
                    $key,
                    $value
                );
            }
        }
        sort($cleanedHeaders);

        return implode("\n", $cleanedHeaders);
    }

    public function checkHeadersAreIdentical(string $headers1, string $headers2): bool
    {
        $headers1 = explode("\n", $headers1);
        $headers2 = explode("\n", $headers2);

        /**
         * @see https://www.php.net/manual/en/function.array-diff.php#120821
         */
        $intersect = array_intersect($headers1, $headers2);
        $headersWhichAreUnique = array_merge(array_diff($headers1, $intersect), array_diff($headers2, $intersect));

        foreach ($headersWhichAreUnique as $headerWhichIsUnique) {
            foreach (self::IGNORED_HEADERS as $ignoredHeader) {
                if (str_starts_with($headerWhichIsUnique, $ignoredHeader)) {
                    continue 2;
                }
            }

            return false;
        }

        return true;
    }

    public function assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(string $pathToProjectRoot, string $pathToDocumentationFile, ResponseInterface $response): void
    {
        $headers = $this->getHeadersFromRequest($response);
        $documentationHeaders = file_get_contents($pathToProjectRoot.$pathToDocumentationFile);
        $this->assertTrue(
            $this->checkHeadersAreIdentical($documentationHeaders, $headers),
            sprintf(
                "Content of file %s should be as following:\n\n%s\n",
                $pathToDocumentationFile,
                $headers
            )
        );
    }

    public function assertBodyInDocumentationIsIdenticalToBodyFromRequest(
        string $pathToProjectRoot,
        string $pathToDocumentationFile,
        ResponseInterface $response,
        bool $isJson = true
    ): void {
        $body = (string) $response->getBody();
        if ($isJson) {
            $body = $this->getFormattedResponseBodyAsJsonString($response);
        }
        $documentationBody = file_get_contents($pathToProjectRoot.$pathToDocumentationFile);
        $this->assertTrue(
            $documentationBody === $body,
            sprintf(
                "Content of file %s should be as following:\n\n%s\n",
                $pathToDocumentationFile,
                $body
            )
        );
    }

    public function getFormattedResponseBodyAsJsonString(ResponseInterface $response): string
    {
        $data = json_decode($response->getBody(), true);
        if (array_key_exists('exception', $data)) {
            unset($data['exception']);
        }
        $jsonString = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        return str_replace('    ', '  ', $jsonString);
    }
}
