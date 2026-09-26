<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController;

use Psr\Http\Message\ResponseInterface;

abstract class BaseRequestTestCase extends \App\Tests\FeatureTests\BaseRequestTestCase
{
    /**
     * @var string[] IGNORED_HEADERS
     */
    private const array IGNORED_HEADERS = ['Date', 'Etag', 'Location', 'Expires'];

    /**
     * @var string[] ELASTICSEARCH_SCORE_KEYS
     */
    private const array ELASTICSEARCH_SCORE_KEYS = ['score', 'maxScore'];

    /**
     * @var string[] REMOVED_HEADERS
     */
    private const array REMOVED_HEADERS = ['X-Debug-Token', 'X-Debug-Token-Link'];

    /**
     * If the environment variable FIX_CONTROLLER_OUTPUT is set, differing documentation files are updated
     * automatically instead of failing the test.
     */
    private function isFixControllerOutputEnabled(): bool
    {
        return array_key_exists('FIX_CONTROLLER_OUTPUT', $_ENV);
    }

    private function fixDocumentationFile(string $pathToProjectRoot, string $pathToDocumentationFile, string $content): void
    {
        echo sprintf(
            "\nAutomatically updated file %s.\n",
            $pathToDocumentationFile
        );
        \Safe\file_put_contents($pathToProjectRoot.$pathToDocumentationFile, $content);
        $this->assertTrue(true);
    }

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
        $areHeadersIdentical = $this->checkHeadersAreIdentical($documentationHeaders, $headers);
        if (!$areHeadersIdentical && $this->isFixControllerOutputEnabled()) {
            $this->fixDocumentationFile($pathToProjectRoot, $pathToDocumentationFile, $headers);

            return;
        }
        $this->assertTrue(
            $areHeadersIdentical,
            sprintf(
                "Content of file %s should be as following:\n\n%s\n",
                $pathToDocumentationFile,
                $headers
            )
        );
    }

    private function isProblemJsonResponse(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 400
            && str_starts_with($response->getHeaderLine('Content-Type'), 'application/problem+json');
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
        if ($this->isProblemJsonResponse($response)) {
            // every problem json response identifies its request as `urn:uuid:<id>`, which differs per request
            $ignoreLinesContainingString[] = '"instance": "urn:uuid:';
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

        $isBodyIdentical = $filteredDocumentationBody === $filteredBody;
        if (!$isBodyIdentical && $this->isFixControllerOutputEnabled()) {
            $this->fixDocumentationFile($pathToProjectRoot, $pathToDocumentationFile, $body);

            return;
        }
        $this->assertTrue(
            $isBodyIdentical,
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

    /**
     * Updates the documentation of a search result. If the documented result differs from the response (ignoring
     * volatile values), the whole file is replaced. Otherwise, only the Elasticsearch scores are updated in place, so
     * that unrelated values like timestamps are not changed.
     */
    private function fixSearchResultDocumentation(
        string $pathToProjectRoot,
        string $pathToDocumentationFile,
        array $rawResponseData,
        string $prettyPrintedRawResponse,
        bool $isIdenticalIgnoringScores,
    ): void {
        if (!$isIdenticalIgnoringScores) {
            $this->fixDocumentationFile($pathToProjectRoot, $pathToDocumentationFile, $prettyPrintedRawResponse);

            return;
        }

        $scores = $this->collectElasticsearchScores($rawResponseData['debug'] ?? []);
        $documentation = file_get_contents($pathToProjectRoot.$pathToDocumentationFile);
        $replacedScores = 0;
        $updatedDocumentation = preg_replace_callback(
            '/("(?:score|maxScore)": )(-?[0-9.eE+-]+)/',
            function (array $matches) use ($scores, &$replacedScores): string {
                return $matches[1].json_encode($scores[$replacedScores++] ?? null);
            },
            $documentation
        );
        if ($replacedScores !== count($scores)) {
            $this->fixDocumentationFile($pathToProjectRoot, $pathToDocumentationFile, $prettyPrintedRawResponse);

            return;
        }
        if ($updatedDocumentation !== $documentation) {
            $this->fixDocumentationFile($pathToProjectRoot, $pathToDocumentationFile, $updatedDocumentation);

            return;
        }
        $this->assertTrue(true);
    }

    /**
     * @return array<int, float|int>
     */
    public function collectElasticsearchScores(array $data): array
    {
        $scores = [];
        foreach ($data as $key => $value) {
            if (in_array($key, self::ELASTICSEARCH_SCORE_KEYS, true)) {
                $scores[] = $value;
            } elseif (is_array($value)) {
                array_push($scores, ...$this->collectElasticsearchScores($value));
            }
        }

        return $scores;
    }

    /**
     * Elasticsearch scores are not stable between runs (e.g. they depend on shard statistics), therefore they are
     * removed before comparing responses.
     */
    public function removeElasticsearchScores(array &$data): void
    {
        foreach (self::ELASTICSEARCH_SCORE_KEYS as $scoreKey) {
            unset($data[$scoreKey]);
        }
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->removeElasticsearchScores($value);
            }
        }
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
            $this->removeElasticsearchScores($debug);
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
            $this->removeElasticsearchScores($debug);
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

        if ($this->isFixControllerOutputEnabled()) {
            $this->fixSearchResultDocumentation(
                $pathToProjectRoot,
                $pathToDocumentationFile,
                $rawResponseData,
                $prettyPrintedRawResponse,
                $responseData == $documentationData
            );

            return;
        }

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
