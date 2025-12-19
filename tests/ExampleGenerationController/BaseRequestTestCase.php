<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController;

use Psr\Http\Message\ResponseInterface;

abstract class BaseRequestTestCase extends \App\Tests\FeatureTests\BaseRequestTestCase
{
    /**
     * @var string[] IGNORED_HEADERS
     */
    private const array IGNORED_HEADERS = ['Date', 'Etag', 'Location'];

    /**
     * @var string[] REMOVED_HEADERS
     */
    private const array REMOVED_HEADERS = ['X-Debug-Token', 'X-Debug-Token-Link'];

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
        bool $isJson = true,
        array $ignoreLinesContainingString = [],
    ): void {
        $body = (string) $response->getBody();
        if ($isJson) {
            $body = $this->getFormattedResponseBodyAsJsonString($response);
        }
        $documentationBody = file_get_contents($pathToProjectRoot.$pathToDocumentationFile);

        $filteredBody = [];
        foreach (explode("\n", $body) as $line) {
            foreach ($ignoreLinesContainingString as $ignoredLine) {
                if (str_contains($line, $ignoredLine)) {
                    continue 2;
                }
            }
            $filteredBody[] = $line;
        }

        $filteredDocumentationBody = [];
        foreach (explode("\n", $documentationBody) as $line) {
            foreach ($ignoreLinesContainingString as $ignoredLine) {
                if (str_contains($line, $ignoredLine)) {
                    continue 2;
                }
            }
            $filteredDocumentationBody[] = $line;
        }

        $this->assertTrue(
            $filteredDocumentationBody === $filteredBody,
            sprintf(
                "Content of file %s should be as following:\n\n%s\n",
                $pathToDocumentationFile,
                $body
            )
        );
    }

    public function getSignatureOfPathResult(array $path): string
    {
        $nodeIds = $path['nodeIds'] ?? [];
        $relationIds = $path['relationIds'] ?? [];

        $parts = [];
        $count = max(count($nodeIds), count($relationIds));

        for ($i = 0; $i < $count; ++$i) {
            if (isset($nodeIds[$i])) {
                $parts[] = $nodeIds[$i];
            }
            if (isset($relationIds[$i])) {
                $parts[] = $relationIds[$i];
            }
        }

        return implode('-', $parts);
    }

    public function assertSearchResultInDocumentationIsIdenticalToSearchResultFromRequest(
        string $pathToProjectRoot,
        string $pathToDocumentationFile,
        ResponseInterface $response,
    ): void {
        $rawResponseData = \Safe\json_decode((string) $response->getBody(), true);
        $responseData = $rawResponseData;

        foreach ($responseData['results'] as &$result) {
            unset($result['data']['created']);
            unset($result['data']['updated']);
        }
        foreach ($responseData['debug'] as &$debug) {
            unset($debug['start']);
            unset($debug['end']);
            unset($debug['duration']);
            foreach ($debug['input']['parameters']['stepResults'] as &$stepResult) {
                if (array_key_exists('paths', $stepResult)) {
                    usort($stepResult['paths'], function ($a, $b) {
                        return strcmp($this->getSignatureOfPathResult($a), $this->getSignatureOfPathResult($b));
                    });
                }
            }
        }

        $documentationData = json_decode(file_get_contents($pathToProjectRoot.$pathToDocumentationFile), true);

        foreach ($documentationData['results'] as &$result) {
            unset($result['data']['created']);
            unset($result['data']['updated']);
        }
        foreach ($documentationData['debug'] as &$debug) {
            unset($debug['start']);
            unset($debug['end']);
            unset($debug['duration']);
            foreach ($debug['input']['parameters']['stepResults'] as &$stepResult) {
                if (array_key_exists('paths', $stepResult)) {
                    usort($stepResult['paths'], function ($a, $b) {
                        return strcmp($this->getSignatureOfPathResult($a), $this->getSignatureOfPathResult($b));
                    });
                }
            }
        }

        $prettyPrintedRawResponse = json_encode(
            $rawResponseData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        $prettyPrintedRawResponse = str_replace('    ', '  ', $prettyPrintedRawResponse);

        $this->assertEquals(
            $responseData,
            $documentationData,
            sprintf(
                "Content of file %s should be as following:\n\n%s\n",
                $pathToDocumentationFile,
                $prettyPrintedRawResponse
            )
        );
    }

    public function getFormattedResponseBodyAsJsonString(ResponseInterface $response): string
    {
        $data = json_decode((string) $response->getBody(), true);
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
