<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\ReadAccessCypherHelper;
use App\Service\ElementManager;
use App\Service\ElementTypeService;
use App\Service\EtagCalculatorService;
use App\Type\ElementKind;
use App\Type\NodeElement;
use App\Type\ResolvedType;
use App\Type\Uuid;
use Beste\Psr\Log\TestLogger;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\DateTimeZoneId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[Small]
#[CoversClass(EtagCalculatorService::class)]
class EtagCalculatorServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildElementTypeService(?ResolvedType $resolved = new ResolvedType(ElementKind::NODE, 'Data'), ?string $nodeLabel = 'Data'): ElementTypeService
    {
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolve(Argument::any())->willReturn($resolved);
        $elementTypeService->resolveNodeLabel(Argument::any())->willReturn($nodeLabel);

        return $elementTypeService->reveal();
    }

    public function testCalculateElementEtagForNodeWhichExists(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($id);

        // assert result
        $this->assertSame('f6unNiDhNmd', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            'MATCH (element:`Data` {id: $elementId}) RETURN element.updated AS updated',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for element.'));
    }

    public function testCalculateElementEtagForRelationWhichExists(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(new ResolvedType(ElementKind::RELATION, 'RELATED')),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($id);

        // assert result
        $this->assertSame('f6unNiDhNmd', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            'MATCH ()-[element:`RELATED` {id: $elementId}]->() RETURN element.updated AS updated',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for element.'));
    }

    public function testCalculateElementEtagForElementWhichDoesNotExist(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => null,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($id);
        $this->assertNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
    }

    public function testCalculateElementEtagReturnsNullWithoutQueryIfTypeIsUnresolved(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(null),
            TestLogger::create(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->assertNull($etagCalculatorService->calculateElementEtag(Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub')));
    }

    public function testCalculateElementEtagEscapesTheResolvedType(): void
    {
        $null = null;
        /** @var ?Statement $statement */
        $statement = null;
        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn(new SummarizedResult($null, []));
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($clientInterface->reveal());

        $etagCalculatorService = new EtagCalculatorService(
            $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(new ResolvedType(ElementKind::NODE, 'Da`ta')),
            TestLogger::create(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // no row at all is treated like an element without updated timestamp
        $this->assertNull($etagCalculatorService->calculateElementEtag(Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub')));
        $this->assertSame('MATCH (element:`Da``ta` {id: $elementId}) RETURN element.updated AS updated', $statement?->getText());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function collectionMethodProvider(): array
    {
        return [
            'children' => ['calculateChildrenCollectionEtag'],
            'parents' => ['calculateParentsCollectionEtag'],
            'related' => ['calculateRelatedCollectionEtag'],
        ];
    }

    #[DataProvider('collectionMethodProvider')]
    public function testCollectionEtagIsNullWithoutQueryIfNodeLabelIsUnresolved(string $method): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(null, null),
            TestLogger::create(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->assertNull($etagCalculatorService->$method(Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub')));
    }

    public function testCalculateElementEtagWithEdgecaseWhereDifferentObjectIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => new stdClass(),
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unable to get DateTime from stdClass.');

        // run service method
        $etagCalculatorService->calculateElementEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
    }

    public function testCalculateChildrenCollectionEtagWithExistingElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '02IiNr0G8viOVKjgkfJb5pRcbhHifRTwfgwml',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                    ]),
                    'childrenCount' => 1,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($id);

        // assert result
        $this->assertSame('1p5bkZfgbcb', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (parent:`Data` {id: \$parentId})\n".
            "MATCH (parent)-[:OWNS]->(children)\n".
            "MATCH (parent)-[relations]->(children)\n".
            "WITH children, relations\n".
            "LIMIT 101\n".
            "WITH children, relations\n".
            "ORDER BY children.id, relations.id\n".
            "WITH COLLECT([children.id, children.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(children) as childrenCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, childrenCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for children collection.'));
    }

    public function testCalculateChildrenCollectionEtagWithNoElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([]),
                    'childrenCount' => 0,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($id);

        // assert result
        $this->assertSame('HWAlNPdJoU', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (parent:`Data` {id: \$parentId})\n".
            "MATCH (parent)-[:OWNS]->(children)\n".
            "MATCH (parent)-[relations]->(children)\n".
            "WITH children, relations\n".
            "LIMIT 101\n".
            "WITH children, relations\n".
            "ORDER BY children.id, relations.id\n".
            "WITH COLLECT([children.id, children.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(children) as childrenCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, childrenCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for children collection.'));
    }

    public function testCalculateChildrenCollectionEtagWithTooManyElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                new DateTimeZoneId(1705772003, 646811000, 'UTC'),
            ]);
        }
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => $resultList,
                    'childrenCount' => 101,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($id);

        // assert result
        $this->assertNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculation of Etag for children collection stopped due to too many children.'));
    }

    public function testCalculateChildrenCollectionEtagDifferentObjectIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new stdClass(),
                        ]),
                    ]),
                    'childrenCount' => 1,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unable to get DateTime from stdClass.');

        // run service method
        $etagCalculatorService->calculateChildrenCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
    }

    public function testCalculateChildrenCollectionEtagWhereNoDataIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            []
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unexpected result.');

        // run service method
        $etagCalculatorService->calculateChildrenCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
    }

    public function testCalculateParentsCollectionEtagWithExistingElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '02IiNr0G8viOVKjgkfJb5pRcbhHifRTwfgwml',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                    ]),
                    'parentsCount' => 1,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateParentsCollectionEtag($id);

        // assert result
        $this->assertSame('1p5bkZfgbcb', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (child:`Data` {id: \$childId})\n".
            "MATCH (child)<-[:OWNS]-(parents)\n".
            "MATCH (child)<-[relations]-(parents)\n".
            "WITH parents, relations\n".
            "LIMIT 101\n".
            "WITH parents, relations\n".
            "ORDER BY parents.id, relations.id\n".
            "WITH COLLECT([parents.id, parents.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(parents) as parentsCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, parentsCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for parents collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for parents collection.'));
    }

    public function testCalculateParentsCollectionEtagWithNoElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([]),
                    'parentsCount' => 0,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateParentsCollectionEtag($id);

        // assert result
        $this->assertSame('HWAlNPdJoU', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (child:`Data` {id: \$childId})\n".
            "MATCH (child)<-[:OWNS]-(parents)\n".
            "MATCH (child)<-[relations]-(parents)\n".
            "WITH parents, relations\n".
            "LIMIT 101\n".
            "WITH parents, relations\n".
            "ORDER BY parents.id, relations.id\n".
            "WITH COLLECT([parents.id, parents.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(parents) as parentsCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, parentsCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for parents collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for parents collection.'));
    }

    public function testCalculateParentsCollectionEtagWithTooManyElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                new DateTimeZoneId(1705772003, 646811000, 'UTC'),
            ]);
        }
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => $resultList,
                    'parentsCount' => 101,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateParentsCollectionEtag($id);

        // assert result
        $this->assertNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for parents collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculation of Etag for parents collection stopped due to too many parents.'));
    }

    public function testCalculateParentsCollectionEtagDifferentObjectIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new stdClass(),
                        ]),
                    ]),
                    'parentsCount' => 1,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unable to get DateTime from stdClass.');

        // run service method
        $etagCalculatorService->calculateParentsCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for parents collection.'));
    }

    public function testCalculateParentsCollectionEtagWhereNoDataIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            []
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unexpected result.');

        // run service method
        $etagCalculatorService->calculateParentsCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for parents collection.'));
    }

    public function testCalculateRelatedCollectionEtagWithExistingElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '02IiNr0G8viOVKjgkfJb5pRcbhHifRTwfgwml',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                    ]),
                    'relatedCount' => 1,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateRelatedCollectionEtag($id);

        // assert result
        $this->assertSame('1p5bkZfgbcb', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (center:`Data` {id: \$centerId})\n".
            "MATCH (center)-[relations]-(related)\n".
            "WITH related, relations\n".
            "LIMIT 101\n".
            "WITH related, relations\n".
            "ORDER BY related.id, relations.id\n".
            "WITH COLLECT([related.id, related.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(related) as relatedCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, relatedCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for related collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for related collection.'));
    }

    public function testCalculateRelatedCollectionEtagWithNoElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([]),
                    'relatedCount' => 0,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateRelatedCollectionEtag($id);

        // assert result
        $this->assertSame('HWAlNPdJoU', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (center:`Data` {id: \$centerId})\n".
            "MATCH (center)-[relations]-(related)\n".
            "WITH related, relations\n".
            "LIMIT 101\n".
            "WITH related, relations\n".
            "ORDER BY related.id, relations.id\n".
            "WITH COLLECT([related.id, related.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(related) as relatedCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, relatedCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for related collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for related collection.'));
    }

    public function testCalculateRelatedCollectionEtagWithTooManyElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                new DateTimeZoneId(1705772003, 646811000, 'UTC'),
            ]);
        }
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => $resultList,
                    'relatedCount' => 101,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateRelatedCollectionEtag($id);

        // assert result
        $this->assertNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for related collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculation of Etag for related collection stopped due to too many related elements.'));
    }

    public function testCalculateRelatedCollectionEtagDifferentObjectIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new stdClass(),
                        ]),
                    ]),
                    'relatedCount' => 1,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unable to get DateTime from stdClass.');

        // run service method
        $etagCalculatorService->calculateRelatedCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for related collection.'));
    }

    public function testCalculateRelatedCollectionEtagWhereNoDataIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            []
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unexpected result.');

        // run service method
        $etagCalculatorService->calculateRelatedCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for related collection.'));
    }

    public function testCalculateIndexCollectionEtagWithExistingElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '02IiNr0G8viOVKjgkfJb5pRcbhHifRTwfgwml',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                    ]),
                    'elementsCount' => 2,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateIndexCollectionEtag($id);

        // assert result
        $this->assertSame('1p5bkZfgbcb', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[relation:OWNS|IS_IN_GROUP|HAS_READ_ACCESS]->(elements)\n".
            'WHERE '.ReadAccessCypherHelper::directReadRelationCondition('relation', 'elements').
            "WITH elements\n".
            "LIMIT 101\n".
            "WITH elements\n".
            "ORDER BY elements.id\n".
            "WITH COLLECT([elements.id, elements.updated]) AS rawTuples, count(elements) as elementsCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, elementsCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for index collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for index collection.'));
    }

    public function testCalculateIndexCollectionEtagWithNoElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([]),
                    'elementsCount' => 0,
                ]),
            ]
        );
        /**
         * @var ?Statement $statement
         */
        $statement = null;

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::that(
            function ($internalStatement) use (&$statement) {
                $statement = $internalStatement;

                return true;
            }
        ))->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateIndexCollectionEtag($id);

        // assert result
        $this->assertSame('HWAlNPdJoU', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[relation:OWNS|IS_IN_GROUP|HAS_READ_ACCESS]->(elements)\n".
            'WHERE '.ReadAccessCypherHelper::directReadRelationCondition('relation', 'elements').
            "WITH elements\n".
            "LIMIT 101\n".
            "WITH elements\n".
            "ORDER BY elements.id\n".
            "WITH COLLECT([elements.id, elements.updated]) AS rawTuples, count(elements) as elementsCount\n".
            "CALL {\n".
            "  WITH rawTuples\n".
            "  UNWIND rawTuples as tuple\n".
            "  WITH tuple ORDER BY tuple[0]\n".
            "  RETURN COLLECT(tuple) AS sortedTuples\n".
            "}\n".
            'RETURN sortedTuples, elementsCount',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for index collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for index collection.'));
    }

    public function testCalculateIndexCollectionEtagWithTooManyElements(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                new DateTimeZoneId(1705772003, 646811000, 'UTC'),
            ]);
        }
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => $resultList,
                    'elementsCount' => 101,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateIndexCollectionEtag($id);

        // assert result
        $this->assertNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for index collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculation of Etag for index collection stopped due to too many index elements.'));
    }

    public function testCalculateIndexCollectionEtagDifferentObjectIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '02IiNr0G8viOVKjOaAG83wxK2tnTIFmidgw60',
                            new stdClass(),
                        ]),
                    ]),
                    'elementsCount' => 1,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unable to get DateTime from stdClass.');

        // run service method
        $etagCalculatorService->calculateIndexCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for index collection.'));
    }

    public function testCalculateIndexCollectionEtagWhereNoDataIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            []
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints()->shouldBeCalledOnce()->willReturn(100);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectExceptionMessage('Unexpected result.');

        // run service method
        $etagCalculatorService->calculateIndexCollectionEtag($id);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for index collection.'));
    }

    public function testCalculateFileEtagUsesStoredSha256Hash(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKk0EPVtyLZhHyrSwZ9Pb54qV');

        $element = new NodeElement();
        $element->addProperty('hasFile', true);
        $element
            ->addProperty('file', [
                'contentLength' => 1024,
                'extension' => 'png',
                'mimeType' => 'image/png',
                'hash' => [
                    'sha256' => str_repeat('a', 64),
                ],
            ])
            ->addProperty('name', 'some name');

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledTimes(2)->willReturn('seed');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // the file etag now always combines the element etag with the stored hash
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNotNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for element.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for file.'));
    }

    public function testCalculateFileEtagFallsBackToAlphabeticallyFirstHashWhenSha256Missing(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkLICSe6spsY4YUBDwkcKDN7');

        $element = new NodeElement();
        $element->addProperty('hasFile', true);
        $element
            ->addProperty('file', [
                'contentLength' => 1024,
                'extension' => 'png',
                'mimeType' => 'image/png',
                'hash' => [
                    'md5' => str_repeat('b', 32),
                    'blake3' => str_repeat('c', 64),
                ],
            ])
            ->addProperty('name', 'some name');

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledTimes(2)->willReturn('seed');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // 'blake3' sorts before 'md5', so it is used as the hash component
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNotNull($etag);
    }

    public function testCalculateFileEtagFallsBackToElementEtagWhenNoHashIsPresent(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkZCVrr3zNzDN2WeKk4HWwn9');

        $element = new NodeElement();
        $element->addProperty('hasFile', true);
        $element
            ->addProperty('file', ['contentLength' => 1024, 'extension' => 'png', 'mimeType' => 'image/png']);

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledTimes(2)->willReturn('seed');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // falls back to the element etag, i.e. a Cypher query, not an S3 call
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNotNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for element.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for file.'));
    }

    public function testCalculateFileEtagFallsBackToElementEtagWhenNoFilePropertyIsPresent(): void
    {
        // an element can lack the 'file' property, e.g. after an upload was rejected due to a mismatched digest

        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjXvVMZ5FrajXyVAYy15Wrxe');

        $element = new NodeElement();
        $element->addProperty('hasFile', true);

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledTimes(2)->willReturn('seed');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method; must not throw despite the element having no 'file' property
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNotNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for file.'));
    }

    public function testCalculateFileEtagReturnsNullWhenElementEtagFallbackAlsoReturnsNull(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKk8w3TYZwdGVcX9NwAzXzc0t');

        $element = new NodeElement();
        $element->addProperty('hasFile', true);

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'updated' => null,
                ]),
            ]
        );

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $this->buildElementTypeService(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method; neither a hash nor an updated timestamp is available anywhere
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNull($etag);
    }

    public function testCalculateFileEtagReturnsNullForElementWithoutFile(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKk0EPVtyLZhHyrSwZ9Pb54qV');

        $element = new NodeElement();
        $element->addProperty('hasFile', false);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldNotBeCalled();

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $this->buildElementTypeService(),
            TestLogger::create(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->assertNull($etagCalculatorService->calculateFileEtag($id));
    }
}
