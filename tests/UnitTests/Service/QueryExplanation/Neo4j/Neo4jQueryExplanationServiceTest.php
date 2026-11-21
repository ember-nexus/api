<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\QueryExplanation\Neo4j;

use App\Service\QueryExplanation\Neo4j\Neo4jQueryExplanationService;
use App\Service\QueryExplanation\QueryExplanationPayloadService;
use App\Type\QueryExplanation;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\DatabaseInfo;
use Laudis\Neo4j\Databags\Neo4jError;
use Laudis\Neo4j\Databags\Notification;
use Laudis\Neo4j\Databags\Position;
use Laudis\Neo4j\Databags\ResultSummary;
use Laudis\Neo4j\Databags\ServerInfo;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\SummaryCounters;
use Laudis\Neo4j\Enum\QueryTypeEnum;
use Laudis\Neo4j\Exception\Neo4jException;
use Laudis\Neo4j\Types\CypherList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionClass;
use RuntimeException;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(Neo4jQueryExplanationService::class)]
class Neo4jQueryExplanationServiceTest extends TestCase
{
    use ProphecyTrait;

    private function neo4jException(string $code, string $message): Neo4jException
    {
        return new Neo4jException([Neo4jError::fromMessageAndCode($code, $message)]);
    }

    /**
     * @param callable(callable): mixed $readTransaction
     */
    private function getService(callable $readTransaction): Neo4jQueryExplanationService
    {
        $client = $this->prophesize(ClientInterface::class);
        $client->readTransaction(Argument::any())->will(static fn (array $args) => $readTransaction($args[0]));
        $entityManager = $this->prophesize(CypherEntityManager::class);
        $entityManager->getClient()->willReturn($client->reveal());

        return new Neo4jQueryExplanationService($entityManager->reveal(), new QueryExplanationPayloadService());
    }

    /**
     * @param Notification[] $notifications
     */
    private function resultWithNotifications(array $notifications): SummarizedResult
    {
        $summary = null;
        $result = new SummarizedResult($summary);
        $summary = new ResultSummary(
            new SummaryCounters(),
            new DatabaseInfo('neo4j'),
            new CypherList($notifications),
            null,
            null,
            new Statement('EXPLAIN q', []),
            QueryTypeEnum::READ_ONLY(),
            0,
            0,
            (new ReflectionClass(ServerInfo::class))->newInstanceWithoutConstructor()
        );

        return $result;
    }

    private function notification(int $line, int $column = 1, int $offset = 0): Notification
    {
        return new Notification('WARNING', 'description', 'Neo.ClientNotification.Statement.Foo', new Position($column, $offset, $line), 'title', 'cat');
    }

    public function testNonNeo4jFailureIsNotExplained(): void
    {
        $service = $this->getService(fn () => $this->fail('must not run EXPLAIN'));

        $this->assertNull($service->explain(new Exception('x'), 'MATCH'));
    }

    public function testExplainRejectionOnFirstLineShiftsPositionAndTrimsMessage(): void
    {
        $original = $this->neo4jException('Neo.ClientError.Statement.SyntaxError', 'orig');
        $rejection = $this->neo4jException(
            'Neo.ClientError.Statement.SyntaxError',
            "Variable `u` not defined (line 1, column 82 (offset: 81))\n\"EXPLAIN MATCH ... u.x\"\n ^"
        );
        $tsx = $this->prophesize(TransactionInterface::class);
        $tsx->run('EXPLAIN MATCH q', ['p' => 1])->willThrow($rejection);
        $service = $this->getService(fn (callable $callback) => $callback($tsx->reveal()));

        $explanation = $service->explain($original, 'MATCH q', ['p' => 1]);

        $this->assertSame('neo4j', $explanation->source);
        $this->assertSame(QueryExplanation::CODE_INVALID_QUERY, $explanation->code);
        $this->assertTrue($explanation->isUserError);
        $this->assertTrue($explanation->isMessageSafe);
        $this->assertSame(
            [
                'code' => 'Neo.ClientError.Statement.SyntaxError',
                'message' => 'Variable `u` not defined',
                'position' => ['line' => 1, 'column' => 74, 'offset' => 73],
            ],
            $explanation->diagnostics
        );
    }

    public function testExplainRejectionOnLaterLineKeepsPosition(): void
    {
        $rejection = $this->neo4jException(
            'Neo.ClientError.Statement.SyntaxError',
            "Bad (line 2, column 3 (offset: 20))\nsnippet"
        );
        $service = $this->getService(fn () => throw $rejection);

        $explanation = $service->explain($this->neo4jException('Neo.ClientError.Statement.SyntaxError', 'x'), "a\nb");

        $this->assertSame(['line' => 2, 'column' => 3, 'offset' => 20], $explanation->diagnostics['position']);
    }

    public function testExplainRejectionPositionIsClampedToStartOfQuery(): void
    {
        $rejection = $this->neo4jException('Neo.ClientError.Statement.SyntaxError', 'Bad (line 1, column 2 (offset: 1))');
        $service = $this->getService(fn () => throw $rejection);

        $explanation = $service->explain($rejection, 'q');

        $this->assertSame(['line' => 1, 'column' => 1, 'offset' => 0], $explanation->diagnostics['position']);
    }

    public function testExplainRejectionWithoutPositionAndEmptyMessage(): void
    {
        $rejection = $this->neo4jException('Neo.ClientError.Statement.SyntaxError', '');
        $service = $this->getService(fn () => throw $rejection);

        $explanation = $service->explain($rejection, 'q');

        $this->assertFalse($explanation->isMessageSafe);
        $this->assertSame(['code' => 'Neo.ClientError.Statement.SyntaxError', 'message' => ''], $explanation->diagnostics);
    }

    public function testExplainRejectionByDatabaseErrorIsNeitherSafeNorUserError(): void
    {
        $rejection = $this->neo4jException('Neo.DatabaseError.General.UnknownError', 'boom');
        $service = $this->getService(fn () => throw $rejection);

        $explanation = $service->explain($rejection, 'q');

        $this->assertFalse($explanation->isUserError);
        $this->assertFalse($explanation->isMessageSafe);
    }

    public function testNullMessageOfExplainRejectionIsHandled(): void
    {
        $rejection = new Neo4jException([Neo4jError::fromMessageAndCode('Neo.ClientError.Statement.SyntaxError', null)]);
        $service = $this->getService(fn () => throw $rejection);

        $explanation = $service->explain($rejection, 'q');

        $this->assertSame('', $explanation->diagnostics['message']);
    }

    public function testWrappedNeo4jExceptionIsFound(): void
    {
        $original = $this->neo4jException('Neo.ClientError.Statement.TypeError', 'contains data');
        $service = $this->getService(fn () => $this->resultWithNotifications([]));

        $explanation = $service->explain(new RuntimeException('wrapper', 0, $original), 'q');

        $this->assertSame(QueryExplanation::CODE_EXECUTION_FAILED, $explanation->code);
        $this->assertTrue($explanation->isUserError);
        $this->assertFalse($explanation->isMessageSafe);
        $this->assertSame(['code' => 'Neo.ClientError.Statement.TypeError'], $explanation->diagnostics);
    }

    public function testRuntimeFailureIncludesNotificationsAndNeverTheMessage(): void
    {
        $original = $this->neo4jException('Neo.ClientError.Statement.TypeError', 'secret value 42');
        $notifications = [$this->notification(0), $this->notification(1, 10, 9)];
        $service = $this->getService(fn () => $this->resultWithNotifications($notifications));

        $explanation = $service->explain($original, 'q');

        $this->assertStringNotContainsString('secret', json_encode($explanation->diagnostics));
        $this->assertSame(
            [
                [
                    'code' => 'Neo.ClientNotification.Statement.Foo',
                    'title' => 'title',
                    'severity' => 'WARNING',
                    'description' => 'description',
                ],
                [
                    'code' => 'Neo.ClientNotification.Statement.Foo',
                    'title' => 'title',
                    'severity' => 'WARNING',
                    'description' => 'description',
                    'position' => ['line' => 1, 'column' => 2, 'offset' => 1],
                ],
            ],
            $explanation->diagnostics['notifications']
        );
    }

    public function testNotificationsAreCapped(): void
    {
        $original = $this->neo4jException('Neo.ClientError.Statement.TypeError', 'x');
        $service = $this->getService(fn () => $this->resultWithNotifications(array_fill(0, 15, $this->notification(0))));

        $explanation = $service->explain($original, 'q');

        $this->assertCount(10, $explanation->diagnostics['notifications']);
    }

    public function testNonClientRuntimeFailureIsNotAUserError(): void
    {
        $original = $this->neo4jException('Neo.TransientError.General.Foo', 'x');
        $service = $this->getService(fn () => $this->resultWithNotifications([]));

        $explanation = $service->explain($original, 'q');

        $this->assertFalse($explanation->isUserError);
    }

    public function testFailingExplainWithOtherExceptionFallsBackToCodeOnly(): void
    {
        $original = $this->neo4jException('Neo.ClientError.Statement.TypeError', 'x');
        $service = $this->getService(fn () => throw new RuntimeException('connection lost'));

        $explanation = $service->explain($original, 'q');

        $this->assertSame(['code' => 'Neo.ClientError.Statement.TypeError'], $explanation->diagnostics);
    }
}
