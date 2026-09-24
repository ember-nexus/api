<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\Event\ElementPreDeleteEvent;
use App\EventSystem\EntityManager\EventListener\ExpireEtagOnChangeEventListener;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\RedisKeyFactory;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(ExpireEtagOnChangeEventListener::class)]
class ExpireEtagOnChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private const string ELEMENT_ID = '224a787e-3b32-4822-8697-61047175505d';
    private const string CHILD_ID = '11111111-1111-4111-8111-111111111111';
    private const string PARENT_ID = '22222222-2222-4222-8222-222222222222';
    private const string RELATED_ID = '33333333-3333-4333-8333-333333333333';
    private const string USER_ID = '44444444-4444-4444-8444-444444444444';
    private const string START_ID = '55555555-5555-4555-8555-555555555555';
    private const string END_ID = '66666666-6666-4666-8666-666666666666';

    /**
     * @param array<string, mixed>|null $neighbours   result of the first query, null for no row at all
     * @param array<string, mixed>|null $relation     result of the second query, null for no row at all
     * @param string[]                  $expectedKeys
     */
    private function runListener(
        callable $call,
        ?array $neighbours,
        ?array $relation,
        array $expectedKeys,
        ?Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory = null,
    ): void {
        $null = null;
        $neighbourResult = new SummarizedResult($null, null === $neighbours ? [] : [new CypherMap($neighbours)]);
        $relationResult = new SummarizedResult($null, null === $relation ? [] : [new CypherMap($relation)]);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->willReturn($neighbourResult, $relationResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($clientInterface->reveal());

        $expiredKeys = [];
        $redisClient = $this->prophesize(Client::class);
        $redisClient->expire(Argument::type('string'), 0)->will(function ($args) use (&$expiredKeys) {
            $expiredKeys[] = $args[0];

            return 1;
        });

        $listener = new ExpireEtagOnChangeEventListener(
            $redisClient->reveal(),
            $cypherEntityManager->reveal(),
            new RedisKeyFactory(),
            $server500LogicErrorExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
        $call($listener);

        sort($expiredKeys);
        sort($expectedKeys);
        $this->assertSame($expectedKeys, $expiredKeys);
    }

    private function createNode(): NodeElement
    {
        $node = new NodeElement();
        $node->setId(Uuid::fromString(self::ELEMENT_ID));

        return $node;
    }

    /**
     * @return array<string, CypherList<string>>
     */
    private function createNeighbours(): array
    {
        return [
            'childrenList' => new CypherList([self::CHILD_ID]),
            'parentsList' => new CypherList([self::PARENT_ID]),
            'relatedList' => new CypherList([self::RELATED_ID]),
            'indexList' => new CypherList([self::USER_ID]),
        ];
    }

    /**
     * @return string[]
     */
    private function getExpectedKeysOfNode(): array
    {
        return [
            'etag:element:'.self::ELEMENT_ID,
            // the file ETag depends on name, file property and element ETag, so it expires with the element
            'etag:file:'.self::ELEMENT_ID,
            'etag:parents:'.self::CHILD_ID,
            'etag:children:'.self::PARENT_ID,
            'etag:related:'.self::RELATED_ID,
            'etag:index:'.self::USER_ID,
        ];
    }

    public function testPostCreateExpiresEtagsOfElementAndItsNeighbours(): void
    {
        $event = new ElementPostCreateEvent($this->createNode());

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostCreateEvent($event),
            $this->createNeighbours(),
            null,
            $this->getExpectedKeysOfNode()
        );
    }

    public function testPostMergeExpiresEtagsOfElementAndItsNeighbours(): void
    {
        $event = new ElementPostMergeEvent($this->createNode());

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostMergeEvent($event),
            $this->createNeighbours(),
            null,
            $this->getExpectedKeysOfNode()
        );
    }

    public function testPreDeleteExpiresEtagsOfElementAndItsNeighbours(): void
    {
        $event = new ElementPreDeleteEvent($this->createNode());

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPreDeleteEvent($event),
            $this->createNeighbours(),
            null,
            $this->getExpectedKeysOfNode()
        );
    }

    public function testElementWithoutNeighboursOnlyExpiresItsOwnEtags(): void
    {
        $event = new ElementPostMergeEvent($this->createNode());

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostMergeEvent($event),
            null,
            null,
            [
                'etag:element:'.self::ELEMENT_ID,
                'etag:file:'.self::ELEMENT_ID,
            ]
        );
    }

    public function testNormalRelationAlsoExpiresRelatedCollectionsOfStartAndEnd(): void
    {
        $relation = new RelationElement();
        $relation->setId(Uuid::fromString(self::ELEMENT_ID));
        $event = new ElementPostMergeEvent($relation);

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostMergeEvent($event),
            null,
            ['start.id' => self::START_ID, 'type' => 'RELATED', 'end.id' => self::END_ID],
            [
                'etag:element:'.self::ELEMENT_ID,
                'etag:file:'.self::ELEMENT_ID,
                'etag:related:'.self::START_ID,
                'etag:related:'.self::END_ID,
            ]
        );
    }

    public function testOwnsRelationAlsoExpiresChildrenOfStartAndParentsOfEnd(): void
    {
        $relation = new RelationElement();
        $relation->setId(Uuid::fromString(self::ELEMENT_ID));
        $event = new ElementPostCreateEvent($relation);

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostCreateEvent($event),
            null,
            ['start.id' => self::START_ID, 'type' => 'OWNS', 'end.id' => self::END_ID],
            [
                'etag:element:'.self::ELEMENT_ID,
                'etag:file:'.self::ELEMENT_ID,
                'etag:related:'.self::START_ID,
                'etag:related:'.self::END_ID,
                'etag:children:'.self::START_ID,
                'etag:parents:'.self::END_ID,
            ]
        );
    }

    public function testElementWithoutIdThrows(): void
    {
        $listener = new ExpireEtagOnChangeEventListener(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(CypherEntityManager::class)->reveal(),
            new RedisKeyFactory(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to expire etag for element with no identifier.');
        $listener->onElementPostMergeEvent(new ElementPostMergeEvent(new NodeElement()));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNeighbourListProvider(): array
    {
        return [
            'children list' => ['childrenList'],
            'parents list' => ['parentsList'],
            'related list' => ['relatedList'],
            'index list' => ['indexList'],
        ];
    }

    #[DataProvider('invalidNeighbourListProvider')]
    public function testInvalidNeighbourListThrowsLogicError(string $brokenList): void
    {
        $neighbours = $this->createNeighbours();
        $neighbours[$brokenList] = 'not a list';

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(new Server500LogicErrorException('type'));

        $event = new ElementPostMergeEvent($this->createNode());

        $this->expectException(Server500LogicErrorException::class);
        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostMergeEvent($event),
            $neighbours,
            null,
            [],
            $server500LogicErrorExceptionFactory->reveal()
        );
    }

    public function testNonStringNeighbourIdThrowsLogicError(): void
    {
        $neighbours = $this->createNeighbours();
        $neighbours['childrenList'] = new CypherList([123]);

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(new Server500LogicErrorException('type'));

        $event = new ElementPostMergeEvent($this->createNode());

        $this->expectException(Server500LogicErrorException::class);
        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostMergeEvent($event),
            $neighbours,
            null,
            [],
            $server500LogicErrorExceptionFactory->reveal()
        );
    }

    public function testNonStringRelationEndpointThrowsLogicError(): void
    {
        $relation = new RelationElement();
        $relation->setId(Uuid::fromString(self::ELEMENT_ID));

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(new Server500LogicErrorException('type'));

        $event = new ElementPostMergeEvent($relation);

        $this->expectException(Server500LogicErrorException::class);
        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostMergeEvent($event),
            null,
            ['start.id' => null, 'type' => 'RELATED', 'end.id' => self::END_ID],
            [],
            $server500LogicErrorExceptionFactory->reveal()
        );
    }
}
