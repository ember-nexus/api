<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Security;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\AccessResultCacheService;
use App\Service\ElementTypeService;
use App\Type\AccessType;
use App\Type\ElementKind;
use App\Type\ResolvedType;
use App\Type\Uuid;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * Covers only the audit-logging side effect of `hasAccessToElement()` (closes #347); the query logic itself is
 * covered by the EndToEndTests/Security suite, which exercises it against a real database.
 */
#[Small]
#[CoversClass(AccessChecker::class)]
class AccessCheckerTest extends TestCase
{
    use ProphecyTrait;

    private function buildElementTypeService(?ResolvedType $resolved): ElementTypeService
    {
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolve(Argument::any())->willReturn($resolved);
        $elementTypeService->resolveNodeLabel(Argument::any())->willReturn($resolved?->isNode() ? $resolved->getType() : null);
        $elementTypeService->resolveRelationType(Argument::any())->willReturn($resolved?->isRelation() ? $resolved->getType() : null);

        return $elementTypeService->reveal();
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     *
     * @param ObjectProphecy<ClientInterface>|null $clientProphecy
     */
    private function buildAccessChecker(
        bool $grantAccess,
        string $elementIdString,
        LoggerInterface $logger,
        AuthProvider $authProvider,
        ?ResolvedType $resolved = new ResolvedType(ElementKind::NODE, 'Data'),
        ?ObjectProphecy $clientProphecy = null,
        ?ElementTypeService $elementTypeService = null,
        ?AccessResultCacheService $accessResultCacheService = null,
    ): AccessChecker {
        if (null === $clientProphecy) {
            $summaryReference = null;
            $clientProphecy = $this->prophesize(ClientInterface::class);
            $clientProphecy->runStatement(Argument::any())->will(function () use (&$summaryReference, $grantAccess) {
                return new SummarizedResult($summaryReference, [new CypherMap(['count' => $grantAccess ? 1 : 0])]);
            });
        }

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($clientProphecy->reveal());

        return new AccessChecker(
            $cypherEntityManager->reveal(),
            $authProvider,
            $logger,
            $elementTypeService ?? $this->buildElementTypeService($resolved),
            $accessResultCacheService ?? new AccessResultCacheService(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    public function testLogsNoticeWhenAccessIsDenied(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();
        $tokenId = Uuid::generate();

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn(false);
        $authProvider->getTokenId()->willReturn($tokenId);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'Access to element was denied.',
            [
                'elementId' => $elementId->toString(),
                'accessType' => AccessType::READ->value,
                'client' => [
                    'user' => $userId->toString(),
                    'token' => $tokenId->toString(),
                ],
            ]
        )->shouldBeCalledOnce();

        $accessChecker = $this->buildAccessChecker(
            false,
            $elementId->toString(),
            $logger->reveal(),
            $authProvider->reveal()
        );

        $this->assertFalse($accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ));
    }

    public function testDoesNotLogWhenAccessIsGranted(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();

        $authProvider = $this->prophesize(AuthProvider::class);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $accessChecker = $this->buildAccessChecker(
            true,
            $elementId->toString(),
            $logger->reveal(),
            $authProvider->reveal()
        );

        $this->assertTrue($accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ));
    }

    public function testLogsAnonymousUserWhenAccessIsDenied(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn(true);
        $authProvider->getTokenId()->willReturn(null);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'Access to element was denied.',
            [
                'elementId' => $elementId->toString(),
                'accessType' => AccessType::READ->value,
                'client' => [
                    'user' => 'anonymous',
                    'token' => null,
                ],
            ]
        )->shouldBeCalledOnce();

        $accessChecker = $this->buildAccessChecker(
            false,
            $elementId->toString(),
            $logger->reveal(),
            $authProvider->reveal()
        );

        $this->assertFalse($accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ));
    }

    public function testUnresolvedElementIsDeniedWithoutQueryAndLogsNotice(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn(true);
        $authProvider->getTokenId()->willReturn(null);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice('Access to element was denied.', Argument::any())->shouldBeCalledTimes(4);

        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->shouldNotBeCalled();

        $accessChecker = $this->buildAccessChecker(true, $elementId->toString(), $logger->reveal(), $authProvider->reveal(), null, $client);

        foreach ([AccessType::READ, AccessType::UPDATE, AccessType::DELETE, AccessType::CREATE] as $accessType) {
            $this->assertFalse($accessChecker->hasAccessToElement($userId, $elementId, $accessType));
        }
        $this->assertNull($accessChecker->getElementType($elementId));
        $this->assertSame([], $accessChecker->getDirectGroupsWithAccessToElement($elementId, AccessType::READ));
    }

    public function testNodeAccessQueryUsesTheEscapedResolvedLabel(): void
    {
        $elementId = Uuid::generate();
        $summaryReference = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::that(
            fn (Statement $statement) => str_contains($statement->getText(), 'MATCH (element:`Da``ta` {id: $elementId})')
        ))->shouldBeCalledOnce()->willReturn(new SummarizedResult($summaryReference, [new CypherMap(['count' => 1])]));

        $accessChecker = $this->buildAccessChecker(
            true,
            $elementId->toString(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(AuthProvider::class)->reveal(),
            new ResolvedType(ElementKind::NODE, 'Da`ta'),
            $client,
        );

        $accessChecker->hasReadAccessToNode(Uuid::generate(), $elementId);
    }

    public function testRelationAccessQueryUsesTheEscapedResolvedType(): void
    {
        $elementId = Uuid::generate();
        $summaryReference = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::that(
            fn (Statement $statement) => str_contains($statement->getText(), '-[element:`REL``X` {id: $elementId}]->')
        ))->shouldBeCalledOnce()->willReturn(new SummarizedResult($summaryReference, []));

        $accessChecker = $this->buildAccessChecker(
            true,
            $elementId->toString(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(AuthProvider::class)->reveal(),
            new ResolvedType(ElementKind::RELATION, 'REL`X'),
            $client,
        );

        $this->assertFalse($accessChecker->hasReadAccessToRelation(Uuid::generate(), $elementId));
    }

    /**
     * @param string[] $grantedNodeIds
     *
     * @return array{0: Uuid[], 1: Uuid[], 2: list<string>}
     */
    private function runMultipleElementsCheck(array $grantedNodeIds): array
    {
        $userId = Uuid::generate();
        $nodeA = Uuid::generate();
        $nodeB = Uuid::generate();
        $relation = Uuid::generate();
        $unknown = Uuid::generate();
        $grantedIds = array_map(fn (string $key) => ['a' => $nodeA, 'b' => $nodeB][$key]->toString(), $grantedNodeIds);

        $types = [
            $nodeA->toString() => new ResolvedType(ElementKind::NODE, 'Data'),
            $nodeB->toString() => new ResolvedType(ElementKind::NODE, 'Group'),
            $relation->toString() => new ResolvedType(ElementKind::RELATION, 'OWNS'),
        ];
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolve(Argument::type(Uuid::class))->will(fn (array $args) => $types[$args[0]->toString()] ?? null);
        $elementTypeService->resolveNodeLabel(Argument::type(Uuid::class))->will(
            fn (array $args) => ($types[$args[0]->toString()] ?? null)?->isNode() ? $types[$args[0]->toString()]->getType() : null
        );
        $elementTypeService->resolveMany(Argument::type('array'))->will(function (array $args) use ($types): array {
            $resolved = [];
            foreach ($args[0] as $id) {
                $resolved[$id->toString()] = $types[$id->toString()] ?? null;
            }

            return $resolved;
        });

        $queries = [];
        $summaryReference = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->will(function (array $args) use (&$queries, &$summaryReference, $relation, $nodeA, $nodeB, $grantedIds) {
            $text = $args[0]->getText();
            $queries[] = $text;
            if (str_contains($text, '-[relation:`OWNS` {id: relationId}]->')) {
                return new SummarizedResult($summaryReference, [new CypherMap([
                    'id' => $relation->toString(),
                    'startId' => $nodeA->toString(),
                    'endId' => $nodeB->toString(),
                ])]);
            }
            $label = str_contains($text, '(element:`Data` {id: elementId})') ? 'Data' : 'Group';
            $nodeId = 'Data' === $label ? $nodeA->toString() : $nodeB->toString();

            return new SummarizedResult($summaryReference, in_array($nodeId, $grantedIds, true) ? [new CypherMap(['elementId' => $nodeId])] : []);
        });

        $accessChecker = $this->buildAccessChecker(
            true,
            $nodeA->toString(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(AuthProvider::class)->reveal(),
            null,
            $client,
            $elementTypeService->reveal(),
        );

        $result = $accessChecker->checkUserAccessToMultipleElements($userId, [$nodeA, $nodeB, $relation, $unknown]);

        return [$result, [$nodeA, $nodeB, $relation], $queries];
    }

    public function testMultipleElementsCheckQueriesPerRelationTypeAndPerNodeLabel(): void
    {
        [$result, $ids, $queries] = $this->runMultipleElementsCheck(['a', 'b']);

        $this->assertEquals($ids, $result);
        $this->assertCount(3, $queries);
        $this->assertStringContainsString('UNWIND $ids AS relationId MATCH (start)-[relation:`OWNS` {id: relationId}]->(end)', $queries[0]);
        $this->assertStringContainsString('MATCH (element:`Data` {id: elementId})', $queries[1]);
        $this->assertStringContainsString('MATCH (element:`Group` {id: elementId})', $queries[2]);
    }

    public function testMultipleElementsCheckDropsRelationsWhoseEndpointIsNotAccessible(): void
    {
        [$result, $ids] = $this->runMultipleElementsCheck(['a']);

        $this->assertEquals([$ids[0]], $result);
    }

    /**
     * @return array{AccessChecker, ObjectProphecy<ClientInterface>, AccessResultCacheService}
     */
    private function buildCountingAccessChecker(bool $grantAccess, LoggerInterface $logger): array
    {
        $summaryReference = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->will(function () use (&$summaryReference, $grantAccess) {
            return new SummarizedResult($summaryReference, [new CypherMap(['count' => $grantAccess ? 1 : 0])]);
        });
        $cache = new AccessResultCacheService();
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn(true);
        $authProvider->getTokenId()->willReturn(null);
        $accessChecker = $this->buildAccessChecker(
            $grantAccess,
            '',
            $logger,
            $authProvider->reveal(),
            new ResolvedType(ElementKind::NODE, 'Data'),
            $client,
            null,
            $cache
        );

        return [$accessChecker, $client, $cache];
    }

    public function testSecondAccessCheckOfTheSameTripleIsAnsweredFromMemoryWithoutQueryOrSecondLog(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice('Access to element was denied.', Argument::any())->shouldBeCalledOnce();
        [$accessChecker, $client] = $this->buildCountingAccessChecker(false, $logger->reveal());

        $this->assertFalse($accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ));
        $this->assertFalse($accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ));

        $client->runStatement(Argument::any())->shouldHaveBeenCalledTimes(1);
    }

    public function testGrantedAnswerIsRememberedToo(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();
        [$accessChecker, $client] = $this->buildCountingAccessChecker(true, $this->prophesize(LoggerInterface::class)->reveal());

        $this->assertTrue($accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ));
        $this->assertTrue($accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ));

        $client->runStatement(Argument::any())->shouldHaveBeenCalledTimes(1);
    }

    public function testDifferentElementUserOrAccessTypeDoNotShareAnAnswer(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();
        [$accessChecker, $client] = $this->buildCountingAccessChecker(true, $this->prophesize(LoggerInterface::class)->reveal());

        $accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ);
        $accessChecker->hasAccessToElement($userId, Uuid::generate(), AccessType::READ);
        $accessChecker->hasAccessToElement(Uuid::generate(), $elementId, AccessType::READ);
        $accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE);

        $client->runStatement(Argument::any())->shouldHaveBeenCalledTimes(4);
    }

    public function testForgettingTheAnswersMakesTheNextCheckAskAgain(): void
    {
        $userId = Uuid::generate();
        $elementId = Uuid::generate();
        [$accessChecker, $client, $cache] = $this->buildCountingAccessChecker(true, $this->prophesize(LoggerInterface::class)->reveal());

        $accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ);
        $cache->forget();
        $accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ);

        $client->runStatement(Argument::any())->shouldHaveBeenCalledTimes(2);
    }

    public function testUnconditionalReadAccessIsTrueWhenAPathWasFound(): void
    {
        $summaryReference = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::that(
            fn (Statement $statement) => str_contains($statement->getText(), 'MATCH (element:`Data` {id: $elementId})')
                && str_contains($statement->getText(), 'relation.onLabel IS NULL')
        ))->shouldBeCalledOnce()->willReturn(new SummarizedResult($summaryReference, [new CypherMap(['count' => 1])]));
        $accessChecker = $this->buildAccessChecker(true, '', $this->prophesize(LoggerInterface::class)->reveal(), $this->prophesize(AuthProvider::class)->reveal(), clientProphecy: $client);

        $this->assertTrue($accessChecker->hasUnconditionalReadAccessToNode(Uuid::generate(), Uuid::generate(), '`Data`'));
    }

    public function testUnconditionalReadAccessIsFalseForZeroCountOrNoRow(): void
    {
        foreach ([[new CypherMap(['count' => 0])], []] as $rows) {
            $summaryReference = null;
            $client = $this->prophesize(ClientInterface::class);
            $client->runStatement(Argument::any())->willReturn(new SummarizedResult($summaryReference, $rows));
            $accessChecker = $this->buildAccessChecker(true, '', $this->prophesize(LoggerInterface::class)->reveal(), $this->prophesize(AuthProvider::class)->reveal(), clientProphecy: $client);

            $this->assertFalse($accessChecker->hasUnconditionalReadAccessToNode(Uuid::generate(), Uuid::generate(), '`Data`'));
        }
    }
}
