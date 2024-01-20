<?php

namespace App\tests\UnitTests\Service;

use App\Service\EtagCalculatorService;
use Beste\Psr\Log\TestLogger;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\DateTimeZoneId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use stdClass;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class EtagCalculatorServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testCalculateElementEtagForNodeWhichExists(): void
    {
        // setup variables
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($uuid);

        // assert result
        $this->assertSame('a85A8IhnHI6', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "OPTIONAL MATCH (node {id: \$elementUuid})\n".
            "OPTIONAL MATCH ()-[relation {id: \$elementUuid}]->()\n".
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
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger
        );

        // run service method
        $etag = $etagCalculatorService->calculateElementEtag($uuid);

        // assert result
        $this->assertSame('a85A8IhnHI6', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "OPTIONAL MATCH (node {id: \$elementUuid})\n".
            "OPTIONAL MATCH ()-[relation {id: \$elementUuid}]->()\n".
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
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger
        );

        $this->expectExceptionMessage('Unable to find node or relation with id 224a787e-3b32-4822-8697-61047175505d.');

        // run service method
        $etagCalculatorService->calculateElementEtag($uuid);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
    }

    public function testCalculateElementEtagWithEdgecaseWhereDifferentObjectIsReturned(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        // setup variables
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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

        $clientInterface = $this->prophesize(ClientInterface::class);
        $clientInterface->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn($queryResult);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldBeCalledOnce()->willReturn($clientInterface->reveal());

        $logger = TestLogger::create();

        // setup service
        $etagCalculatorService = new EtagCalculatorService(
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $logger
        );

        $this->expectExceptionMessage('Expected variable element.updated to be of type Laudis\Neo4j\Types\DateTimeZoneId, got stdClass.');

        // run service method
        $etagCalculatorService->calculateElementEtag($uuid);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for element.'));
    }

    public function testCalculateChildrenCollectionEtagWithExistingElements(): void
    {
        // setup variables
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($uuid);

        // assert result
        $this->assertSame('526poCqn6vm', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (parent {id: \$parentUuid})\n".
            "MATCH (parent)-[:OWNS]->(children)\n".
            "MATCH (parent)-[relations]->(children)\n".
            "WITH children, relations\n".
            "LIMIT 101\n".
            "WITH children, relations\n".
            "ORDER BY children.id, relations.id\n".
            "WITH COLLECT([children.id, children.updated]) + COLLECT([relations.id, relations.updated]) AS allTuples\n".
            "WITH allTuples\n".
            "UNWIND allTuples AS tuple\n".
            "WITH tuple ORDER BY tuple[0]\n".
            'RETURN COLLECT(tuple) AS sortedTuples',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for children collection.'));
    }

    public function testCalculateChildrenCollectionEtagWithNoElements(): void
    {
        // setup variables
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
        $null = null;
        $queryResult = new SummarizedResult(
            $null,
            [
                new CypherMap([
                    'sortedTuples' => new CypherList([]),
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
            $logger
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($uuid);

        // assert result
        $this->assertSame('3F8H5eXjtu0', (string) $etag);

        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertSame(
            "MATCH (parent {id: \$parentUuid})\n".
            "MATCH (parent)-[:OWNS]->(children)\n".
            "MATCH (parent)-[relations]->(children)\n".
            "WITH children, relations\n".
            "LIMIT 101\n".
            "WITH children, relations\n".
            "ORDER BY children.id, relations.id\n".
            "WITH COLLECT([children.id, children.updated]) + COLLECT([relations.id, relations.updated]) AS allTuples\n".
            "WITH allTuples\n".
            "UNWIND allTuples AS tuple\n".
            "WITH tuple ORDER BY tuple[0]\n".
            'RETURN COLLECT(tuple) AS sortedTuples',
            $statement->getText()
        );

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculated Etag for children collection.'));
    }

    public function testCalculateChildrenCollectionEtagWithTooManyElements(): void
    {
        // setup variables
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger
        );

        // run service method
        $etag = $etagCalculatorService->calculateChildrenCollectionEtag($uuid);

        // assert result
        $this->assertNull($etag);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Calculation of Etag for children collection stopped due to too many children.'));
    }

    public function testCalculateChildrenCollectionEtagDifferentObjectIsReturned(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        // setup variables
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger
        );

        $this->expectExceptionMessage('Expected variable element.updated to be of type Laudis\Neo4j\Types\DateTimeZoneId, got stdClass.');

        // run service method
        $etagCalculatorService->calculateChildrenCollectionEtag($uuid);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
    }

    public function testCalculateChildrenCollectionEtagWhereNoDataIsReturned(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        // setup variables
        $uuid = Uuid::fromString('224a787e-3b32-4822-8697-61047175505d');
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
            $logger
        );

        $this->expectExceptionMessage('Unexpected result.');

        // run service method
        $etagCalculatorService->calculateChildrenCollectionEtag($uuid);

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Calculating Etag for children collection.'));
    }
}
