<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\Event\ElementPreDeleteEvent;
use App\EventSystem\EntityManager\EventListener\ExpireEtagOnChangeEventListener;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\RedisKeyFactory;
use App\Service\AppStateService;
use App\Service\ElementTypeService;
use App\Type\AppStateType;
use App\Type\ElementKind;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\ResolvedType;
use App\Type\Uuid;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
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
use Prophecy\Prophecy\ObjectProphecy;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(ExpireEtagOnChangeEventListener::class)]
class ExpireEtagOnChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private const string ELEMENT_ID = '02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub';
    private const string CHILD_ID = '02IiNr0G8viOVKjTVopQ38JnV8PxJrftZpRV7';
    private const string PARENT_ID = '02IiNr0G8viOVKjboyDliyOxJkyYo8OHX5lnv';
    private const string RELATED_ID = '02IiNr0G8viOVKjk87c7OoU78NXAIP6fUM673';
    private const string USER_ID = '02IiNr0G8viOVKjsRH0T4eZGx05lmfp3RcQPi';
    private const string START_ID = '02IiNr0G8viOVKk0kQOokUeQlceNGwXROskip';
    private const string END_ID = '02IiNr0G8viOVKk93ZnAQKjaaFCylDFpM952o';

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
        // only the query matching the resolved kind of the element is run
        $clientInterface->runStatement(Argument::that(
            fn (Statement $statement) => str_starts_with($statement->getText(), 'MATCH (node:`Data` {id: $elementId})')
        ))->willReturn($neighbourResult);
        $clientInterface->runStatement(Argument::that(
            fn (Statement $statement) => str_starts_with($statement->getText(), 'MATCH (start)-[relation:`RELATED` {id: $elementId}]->(end)')
        ))->willReturn($relationResult);

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
            new AppStateService(),
            $this->buildElementTypeService(null === $relation ? new ResolvedType(ElementKind::NODE, 'Data') : new ResolvedType(ElementKind::RELATION, 'RELATED')),
            $server500LogicErrorExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
        $call($listener);

        sort($expiredKeys);
        sort($expectedKeys);
        $this->assertSame($expectedKeys, $expiredKeys);
    }

    private function buildElementTypeService(?ResolvedType $resolved): ElementTypeService
    {
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolve(Argument::any())->willReturn($resolved);

        return $elementTypeService->reveal();
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

    public function testPostCreateOfNodeExpiresOwnEtagsAndIndexCollectionsOfUsersWithAccess(): void
    {
        $node = $this->createNode()->setLabel('Data');
        $event = new ElementPostCreateEvent($node);

        // neighbours of a created node are covered by the events of the relations created with it
        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostCreateEvent($event),
            $this->createNeighbours(),
            null,
            [
                'etag:element:'.self::ELEMENT_ID,
                'etag:file:'.self::ELEMENT_ID,
                'etag:index:'.self::USER_ID,
            ]
        );
    }

    public function testPostCreateOfNodeWithoutLabelOnlyExpiresItsOwnEtagsAndRunsNoQuery(): void
    {
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $expiredKeys = $this->runCreateListenerWithoutQueries($cypherEntityManager, $this->createNode());

        $this->assertSame(['etag:element:'.self::ELEMENT_ID, 'etag:file:'.self::ELEMENT_ID], $expiredKeys);
    }

    public function testPostCreateOfRelationDerivesTheKeysFromTheRelationAndRunsNoQuery(): void
    {
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $expiredKeys = $this->runCreateListenerWithoutQueries($cypherEntityManager, $this->createRelation('RELATED'));

        $this->assertSame([
            'etag:element:'.self::ELEMENT_ID,
            'etag:file:'.self::ELEMENT_ID,
            'etag:related:'.self::START_ID,
            'etag:related:'.self::END_ID,
        ], $expiredKeys);
    }

    public function testPostCreateOfRelationWithoutEndpointsOnlyExpiresItsOwnEtags(): void
    {
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();
        $relation = (new RelationElement())->setId(Uuid::fromString(self::ELEMENT_ID))->setType('OWNS');

        $expiredKeys = $this->runCreateListenerWithoutQueries($cypherEntityManager, $relation);

        $this->assertSame(['etag:element:'.self::ELEMENT_ID, 'etag:file:'.self::ELEMENT_ID], $expiredKeys);
    }

    /**
     * @return string[]
     */
    private function runCreateListenerWithoutQueries(ObjectProphecy $cypherEntityManager, NodeElement|RelationElement $element): array
    {
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
            new AppStateService(),
            $this->buildElementTypeService(null),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
        $listener->onElementPostCreateEvent(new ElementPostCreateEvent($element));

        return $expiredKeys;
    }

    private function createRelation(string $type): RelationElement
    {
        return (new RelationElement())
            ->setId(Uuid::fromString(self::ELEMENT_ID))
            ->setType($type)
            ->setStart(Uuid::fromString(self::START_ID))
            ->setEnd(Uuid::fromString(self::END_ID));
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

    public function testPreDeleteOnlyCollectsKeysAndExpiresNothing(): void
    {
        $event = new ElementPreDeleteEvent($this->createNode());

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPreDeleteEvent($event),
            $this->createNeighbours(),
            null,
            []
        );
    }

    public function testPostDeleteExpiresTheKeysCollectedByPreDeleteOnce(): void
    {
        $preEvent = new ElementPreDeleteEvent($this->createNode());
        $postEvent = new ElementPostDeleteEvent($this->createNode());

        $this->runListener(
            function (ExpireEtagOnChangeEventListener $listener) use ($preEvent, $postEvent): void {
                $listener->onElementPreDeleteEvent($preEvent);
                $listener->onElementPostDeleteEvent($postEvent);
            },
            $this->createNeighbours(),
            null,
            $this->getExpectedKeysOfNode()
        );
    }

    public function testPostDeleteWithoutPreDeleteExpiresElementAndFileEtag(): void
    {
        $event = new ElementPostDeleteEvent($this->createNode());

        $this->runListener(
            fn (ExpireEtagOnChangeEventListener $listener) => $listener->onElementPostDeleteEvent($event),
            null,
            null,
            ['etag:element:'.self::ELEMENT_ID, 'etag:file:'.self::ELEMENT_ID]
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
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $expiredKeys = $this->runCreateListenerWithoutQueries($cypherEntityManager, $this->createRelation('OWNS'));
        sort($expiredKeys);

        $expected = [
            'etag:element:'.self::ELEMENT_ID,
            'etag:file:'.self::ELEMENT_ID,
            'etag:related:'.self::START_ID,
            'etag:related:'.self::END_ID,
            'etag:children:'.self::START_ID,
            'etag:parents:'.self::END_ID,
        ];
        sort($expected);
        $this->assertSame($expected, $expiredKeys);
    }

    public function testUnresolvedElementOnlyExpiresItsOwnEtagsAndRunsNoQuery(): void
    {
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();
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
            new AppStateService(),
            $this->buildElementTypeService(null),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
        $listener->onElementPostMergeEvent(new ElementPostMergeEvent($this->createNode()));
        $listener->onElementPostDeleteEvent(new ElementPostDeleteEvent($this->createNode()));

        $this->assertCount(4, $expiredKeys);
    }

    public function testDoesNothingWhileLoadingBackup(): void
    {
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();
        $redisClient = $this->prophesize(Client::class);
        $redisClient->expire(Argument::cetera())->shouldNotBeCalled();

        $listener = new ExpireEtagOnChangeEventListener(
            $redisClient->reveal(),
            $cypherEntityManager->reveal(),
            new RedisKeyFactory(),
            (new AppStateService())->setAppState(AppStateType::LOADING_BACKUP),
            $this->buildElementTypeService(null),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $listener->onElementPostCreateEvent(new ElementPostCreateEvent($this->createNode()));
        $listener->onElementPostMergeEvent(new ElementPostMergeEvent($this->createNode()));
        $listener->onElementPreDeleteEvent(new ElementPreDeleteEvent($this->createNode()));
    }

    public function testElementWithoutIdThrows(): void
    {
        $listener = new ExpireEtagOnChangeEventListener(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(CypherEntityManager::class)->reveal(),
            new RedisKeyFactory(),
            new AppStateService(),
            $this->buildElementTypeService(null),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to expire etag for element with no identifier.');
        $listener->onElementPostMergeEvent(new ElementPostMergeEvent(new NodeElement()));
    }

    public function testCreatedElementWithoutIdThrows(): void
    {
        $listener = new ExpireEtagOnChangeEventListener(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(CypherEntityManager::class)->reveal(),
            new RedisKeyFactory(),
            new AppStateService(),
            $this->buildElementTypeService(null),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to expire etag for element with no identifier.');
        $listener->onElementPostCreateEvent(new ElementPostCreateEvent(new NodeElement()));
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
