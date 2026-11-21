<?php

declare(strict_types=1);

namespace App\Service\QueryExplanation\Neo4j;

use App\Service\QueryExplanation\QueryExplanationPayloadService;
use App\Type\QueryExplanation;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\Notification;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Exception\Neo4jException;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Throwable;

/**
 * Diagnoses failed neo4j queries by asking the database to EXPLAIN them.
 *
 * - EXPLAIN never executes the query, so its errors only depend on the query text and are safe to be passed on.
 * - If EXPLAIN succeeds, the query failed while running. Runtime errors can contain property values, therefore only
 *   the error code is returned for them.
 * - The query plan itself is never returned, as its cardinality estimates leak the volume of stored data.
 */
class Neo4jQueryExplanationService
{
    public const string SOURCE = 'neo4j';

    private const string EXPLAIN_PREFIX = 'EXPLAIN ';
    private const int MAX_NOTIFICATIONS = 10;
    private const string CLIENT_ERROR_CLASSIFICATION = 'ClientError';

    private const array DIAGNOSTICS_ALLOWLIST = [
        'code',
        'message',
        'position' => ['line', 'column', 'offset'],
        'notifications' => [
            'code',
            'title',
            'severity',
            'description',
            'position' => ['line', 'column', 'offset'],
        ],
    ];

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private QueryExplanationPayloadService $queryExplanationPayloadService,
    ) {
    }

    private function findNeo4jException(Throwable $throwable): ?Neo4jException
    {
        for ($current = $throwable; null !== $current; $current = $current->getPrevious()) {
            if ($current instanceof Neo4jException) {
                return $current;
            }
        }

        return null;
    }

    /**
     * EXPLAIN reports positions relative to the prefixed statement, which shifts the first line.
     *
     * @return array{line: int, column: int, offset: int}
     */
    private function toUserPosition(int $line, int $column, int $offset): array
    {
        if (1 === $line) {
            $column = max(1, $column - strlen(self::EXPLAIN_PREFIX));
            $offset = max(0, $offset - strlen(self::EXPLAIN_PREFIX));
        }

        return ['line' => $line, 'column' => $column, 'offset' => $offset];
    }

    /**
     * @return array{message: string, position?: array{line: int, column: int, offset: int}}
     */
    private function parseMessage(string $rawMessage): array
    {
        // neo4j appends the echoed statement and a caret line after the first line; only the first line is needed
        $firstLine = trim(explode("\n", $rawMessage)[0]);
        $result = ['message' => $firstLine];
        if (1 === \Safe\preg_match('/\s*\(line (\d+), column (\d+) \(offset: (\d+)\)\)\s*$/', $firstLine, $matches)) {
            /** @var array{0: string, 1: string, 2: string, 3: string} $matches */
            $result['message'] = trim(substr($firstLine, 0, -strlen($matches[0])));
            $result['position'] = $this->toUserPosition((int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNotifications(SummarizedResult $result): array
    {
        $notifications = [];
        /** @var Notification $notification */
        foreach ($result->getSummary()->getNotifications() as $notification) {
            $position = $notification->getPosition();
            $data = [
                'code' => $notification->getCode(),
                'title' => $notification->getTitle(),
                'severity' => $notification->getSeverity(),
                'description' => $notification->getDescription(),
            ];
            // line 0 means that the notification is not bound to a position of the query
            if ($position->getLine() >= 1) {
                $data['position'] = $this->toUserPosition($position->getLine(), $position->getColumn(), $position->getOffset());
            }
            $notifications[] = $data;
            if (count($notifications) >= self::MAX_NOTIFICATIONS) {
                break;
            }
        }

        return $notifications;
    }

    /**
     * Returns null if the failure was not caused by neo4j.
     *
     * @param array<string, mixed> $parameters
     */
    public function explain(Throwable $failure, string $query, array $parameters = []): ?QueryExplanation
    {
        $original = $this->findNeo4jException($failure);
        if (null === $original) {
            return null;
        }
        $isClientError = self::CLIENT_ERROR_CLASSIFICATION === $original->getClassification();

        try {
            $result = $this->cypherEntityManager->getClient()->readTransaction(
                static fn (TransactionInterface $tsx): SummarizedResult => $tsx->run(self::EXPLAIN_PREFIX.$query, $parameters)
            );
            $notifications = $this->getNotifications($result);
        } catch (Neo4jException $explainFailure) {
            // the statement is already rejected before being executed -> only derived from the query text
            $parsedMessage = $this->parseMessage($explainFailure->getNeo4jMessage() ?? '');

            return new QueryExplanation(
                self::SOURCE,
                QueryExplanation::CODE_INVALID_QUERY,
                self::CLIENT_ERROR_CLASSIFICATION === $explainFailure->getClassification(),
                $this->queryExplanationPayloadService->sanitize(
                    ['code' => $explainFailure->getNeo4jCode(), ...$parsedMessage],
                    self::DIAGNOSTICS_ALLOWLIST
                ),
                '' !== $parsedMessage['message'] && self::CLIENT_ERROR_CLASSIFICATION === $explainFailure->getClassification()
            );
        } catch (Throwable) {
            $notifications = [];
        }

        // the query is valid, therefore the failure happened while running it -> code only
        $data = ['code' => $original->getNeo4jCode()];
        if (count($notifications) > 0) {
            $data['notifications'] = $notifications;
        }

        return new QueryExplanation(
            self::SOURCE,
            QueryExplanation::CODE_EXECUTION_FAILED,
            $isClientError,
            $this->queryExplanationPayloadService->sanitize($data, self::DIAGNOSTICS_ALLOWLIST),
        );
    }
}
