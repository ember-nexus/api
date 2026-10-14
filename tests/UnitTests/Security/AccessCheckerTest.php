<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Security;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\AccessType;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
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

    private function buildAccessChecker(bool $grantAccess, string $elementIdString, LoggerInterface $logger, AuthProvider $authProvider): AccessChecker
    {
        $summaryReference = null;
        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->runStatement(Argument::any())->will(function (array $args) use (&$summaryReference, $elementIdString, $grantAccess) {
            $text = $args[0]->getText();
            if (str_contains($text, 'RETURN node.id, relation.id')) {
                return new SummarizedResult($summaryReference, [new CypherMap(['node.id' => $elementIdString, 'relation.id' => null])]);
            }

            return new SummarizedResult($summaryReference, [new CypherMap(['count' => $grantAccess ? 1 : 0])]);
        });

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($clientProphecy->reveal());

        return new AccessChecker(
            $cypherEntityManager->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
            $authProvider,
            $logger
        );
    }

    public function testLogsNoticeWhenAccessIsDenied(): void
    {
        $userId = Uuid::uuid4();
        $elementId = Uuid::uuid4();
        $tokenId = Uuid::uuid4();

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
        $userId = Uuid::uuid4();
        $elementId = Uuid::uuid4();

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
        $userId = Uuid::uuid4();
        $elementId = Uuid::uuid4();

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
}
