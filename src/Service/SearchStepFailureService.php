<?php

declare(strict_types=1);

namespace App\Service;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Exception\ProblemJsonException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\QueryExplanation\Elasticsearch\ElasticsearchQueryExplanationService;
use App\Service\QueryExplanation\Neo4j\Neo4jQueryExplanationService;
use App\Type\QueryExplanation;
use App\Type\SearchStepType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns a failed search step into a Problem JSON exception.
 *
 * A failed search step is never reported as `500`: the server is not at fault if a query can not be run, and the
 * behavior of the databases is not ours to classify. The response is always a `400`.
 *
 * Problem JSON exceptions which already got created by a step (e.g. broken expressions) only get the debug data added
 * if they are a `400`.
 *
 * If `search.explainFailures` is disabled, the response is short and contains neither debug data nor failure details.
 *
 * Otherwise, the response contains
 * - `debug`: the debug data of all steps before the failed one, same as in successful responses,
 * - `failedStep`: index, type and debug data of the failed step up to the point of failure, and, if the failure could
 *   be diagnosed, the source, an ember nexus code and the allowlisted database output in `failedStep.diagnostics`.
 *
 * Raw exception messages are never part of the response, as they can contain stored data; they only get logged.
 */
class SearchStepFailureService
{
    public function __construct(
        private ElasticsearchQueryExplanationService $elasticsearchQueryExplanationService,
        private Neo4jQueryExplanationService $neo4jQueryExplanationService,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private LoggerInterface $logger,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    private function explain(SearchStepEvent $event, Throwable $failure): ?QueryExplanation
    {
        $debugData = $event->getDebugData();
        $query = $event->getQuery();
        $resolvedQuery = $event->getResolvedQuery();
        /** @psalm-suppress MixedAssignment */
        $indices = $debugData['indices'] ?? null;

        return match ($event->getType()) {
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN => null === $resolvedQuery
                ? null
                : $this->elasticsearchQueryExplanationService->explain($failure, $resolvedQuery, is_string($indices) ? $indices : '*'),
            SearchStepType::CYPHER_PATH_SUBSET => is_string($query)
                ? $this->neo4jQueryExplanationService->explain($failure, $query, $event->getParameters())
                : null,
            default => null,
        };
    }

    private function createLogMessage(int $stepIndex, SearchStepType $type, Throwable $failure): string
    {
        return sprintf(
            'Exception of type %s got thrown during search step %d (type %s): %s',
            get_debug_type($failure),
            $stepIndex + 1,
            $type->value,
            $failure->getMessage()
        );
    }

    /**
     * @param array<int, array<string, mixed>> $debugDataOfPreviousSteps
     */
    public function createException(int $stepIndex, SearchStepEvent $event, array $debugDataOfPreviousSteps, Throwable $failure): ProblemJsonException
    {
        $type = $event->getType();
        $explainFailures = $this->emberNexusConfiguration->isSearchExplainFailures();

        if ($failure instanceof ProblemJsonException) {
            // already a helpful response, e.g. bad content; other statuses, e.g. the 422 of an expression timeout, are not
            // query errors and stay as they are, as the debug data would only repeat the possibly huge input
            if (!$explainFailures || 400 !== $failure->getStatus()) {
                return $failure;
            }

            return $failure->withAdditionalProperties([
                'debug' => $debugDataOfPreviousSteps,
                'failedStep' => ['index' => $stepIndex, 'type' => $type->value, 'debug' => $event->getDebugData()],
            ]);
        }

        $this->logger->error($this->createLogMessage($stepIndex, $type, $failure));

        if (!$explainFailures) {
            return $this->client400BadContentExceptionFactory->createFromDetail(
                sprintf('Search step %d (type %s) failed.', $stepIndex + 1, $type->value),
                $failure
            );
        }

        $failedStep = [
            'index' => $stepIndex,
            'type' => $type->value,
            'debug' => $event->getDebugData(),
        ];
        $explanation = $this->explain($event, $failure);
        if (null === $explanation) {
            return $this->client400BadContentExceptionFactory->createFromDetail(
                sprintf('Search step %d (type %s) failed. The reason can not be shown safely; reference the instance of this response when looking it up in the log.', $stepIndex + 1, $type->value),
                $failure,
                ['debug' => $debugDataOfPreviousSteps, 'failedStep' => $failedStep]
            );
        }

        $failedStep['source'] = $explanation->source;
        $failedStep['code'] = $explanation->code;
        if (count($explanation->diagnostics) > 0) {
            $failedStep['diagnostics'] = $explanation->diagnostics;
        }
        $detail = $explanation->isMessageSafe
            ? sprintf('Search step %d (type %s) was rejected by %s, see failedStep.diagnostics for the reason.', $stepIndex + 1, $type->value, $explanation->source)
            : sprintf('Search step %d (type %s) failed while %s was running it. The database message is withheld, as it might contain stored data; see failedStep.diagnostics for the error code.', $stepIndex + 1, $type->value, $explanation->source);

        return $this->client400BadContentExceptionFactory->createFromDetail(
            $detail,
            $failure,
            ['debug' => $debugDataOfPreviousSteps, 'failedStep' => $failedStep]
        );
    }
}
