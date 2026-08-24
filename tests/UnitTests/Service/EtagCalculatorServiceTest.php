<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\ElementManager;
use App\Service\EtagCalculatorService;
use App\Type\NodeElement;
use Beste\Psr\Log\TestLogger;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\DateTimeZoneId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
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

    public function testCalculateElementEtagForNodeWhichExists(): void
    {
        // setup variables
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'node.updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                    'relation.updated' => null,
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($id);

        // assert result
        $this->assertSame('Rh8DXSRXuja', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "OPTIONAL MATCH (node {id: \$elementId})\n".
            "OPTIONAL MATCH ()-[relation {id: \$elementId}]->()\n".
            'RETURN node.updated, relation.updated',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for element.'));
    }

    public function testCalculateElementEtagForRelationWhichExists(): void
    {
        // setup variables
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'node.updated' => null,
                    'relation.updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($id);

        // assert result
        $this->assertSame('Rh8DXSRXuja', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "OPTIONAL MATCH (node {id: \$elementId})\n".
            "OPTIONAL MATCH ()-[relation {id: \$elementId}]->()\n".
            'RETURN node.updated, relation.updated',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for element.'));
    }

    public function testCalculateElementEtagForElementWhichDoesNotExist(): void
    {
        // setup variables
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'node.updated' => null,
                    'relation.updated' => null,
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($id);
        $this->assertNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
    }

    public function testCalculateElementEtagWithEdgecaseWhereDifferentObjectIsReturned(): void
    {
        // setup variables
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'node.updated' => new stdClass(),
                    'relation.updated' => null,
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '2c42deee-ad24-4f04-bb37-7c31fd5b3345',
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($id);

        // assert result
        $this->assertSame('EKHX4b5HhHX', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (parent {id: \$parentId})\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($id);

        // assert result
        $this->assertSame('3F8H5eXjtu0', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (parent {id: \$parentId})\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '2c42deee-ad24-4f04-bb37-7c31fd5b3345',
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateParentsCollectionEtag($id);

        // assert result
        $this->assertSame('EKHX4b5HhHX', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (child {id: \$childId})\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateParentsCollectionEtag($id);

        // assert result
        $this->assertSame('3F8H5eXjtu0', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (child {id: \$childId})\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '2c42deee-ad24-4f04-bb37-7c31fd5b3345',
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateRelatedCollectionEtag($id);

        // assert result
        $this->assertSame('EKHX4b5HhHX', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (center {id: \$centerId})\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateRelatedCollectionEtag($id);

        // assert result
        $this->assertSame('3F8H5eXjtu0', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (center {id: \$centerId})\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
                            new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                        ]),
                        new CypherList([
                            '2c42deee-ad24-4f04-bb37-7c31fd5b3345',
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateIndexCollectionEtag($id);

        // assert result
        $this->assertSame('EKHX4b5HhHX', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[:OWNS|IS_IN_GROUP|HAS_READ_ACCESS]->(elements)\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method
        $etag = $etagCalculatorService->calculateIndexCollectionEtag($id);

        // assert result
        $this->assertSame('3F8H5eXjtu0', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[:OWNS|IS_IN_GROUP|HAS_READ_ACCESS]->(elements)\n".
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $resultList = [];
        for ($i = 0; $i < 101; ++$i) {
            $resultList[] = new CypherList([
                '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([
                        new CypherList([
                            '06f5da99-dfca-43c9-9d5f-3254c0d5f3c9',
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
        $id = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
        $id = Uuid::fromString('544e0cf6-d351-435c-828f-7a0762240ce6');

        $element = new NodeElement();
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

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method; no Cypher query (i.e. no calculateElementEtag() fallback) should have happened at
        // all, per the prophecy expectation above
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertSame('K6XEZuifVAv', (string) $etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for file.'));
    }

    public function testCalculateFileEtagFallsBackToAlphabeticallyFirstHashWhenSha256Missing(): void
    {
        // setup variables
        $id = Uuid::fromString('7f8e9d0c-1b2a-4c3d-9e8f-0a1b2c3d4e5f');

        $element = new NodeElement();
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

        // setup service dependencies
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getCacheEtagSeed()->shouldBeCalledOnce()->willReturn('seed');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($id))->shouldBeCalledOnce()->willReturn($element);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method; 'blake3' sorts before 'md5' alphanumerically, so it is used. The Cypher client
        // prophecy above (shouldNotBeCalled) proves that a hash was actually picked here, rather than falling
        // back to calculateElementEtag().
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNotNull($etag);
    }

    public function testCalculateFileEtagFallsBackToElementEtagWhenNoHashIsPresent(): void
    {
        // setup variables
        $id = Uuid::fromString('9c1a0c0e-6b8f-4b3d-9b1e-2b1a0c0e6b8f');

        $element = new NodeElement();
        $element
            ->addProperty('file', ['contentLength' => 1024, 'extension' => 'png', 'mimeType' => 'image/png']);

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'node.updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                    'relation.updated' => null,
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method; falls back to calculateElementEtag(), i.e. a Cypher query, not an S3 call
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNotNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for element.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for file.'));
    }

    public function testCalculateFileEtagFallsBackToElementEtagWhenNoFilePropertyIsPresent(): void
    {
        // an S3 object can exist without the element ever having its 'file' property set, e.g. when an upload is
        // rejected after the object was already written (a mismatched Content-Digest); this must not crash.

        // setup variables
        $id = Uuid::fromString('1a2b3c4d-5e6f-4a1b-8c9d-0e1f2a3b4c5d');

        $element = new NodeElement();

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'node.updated' => new DateTimeZoneId(1705772003, 646811000, 'UTC'),
                    'relation.updated' => null,
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
        $id = Uuid::fromString('662a045f-7d90-4fa2-85f8-9f972f2bdbd3');

        $element = new NodeElement();

        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'node.updated' => null,
                    'relation.updated' => null,
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
            $logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        // run service method; neither a hash nor an updated timestamp is available anywhere
        $etag = $etagCalculatorService->calculateFileEtag($id);
        $this->assertNull($etag);
    }
}
