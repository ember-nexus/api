<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client403ForbiddenException;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Security\AccessChecker;
use App\Service\AccessGrantingRelationGuardService;
use App\Type\AccessType;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(AccessGrantingRelationGuardService::class)]
class AccessGrantingRelationGuardServiceTest extends TestCase
{
    use ProphecyTrait;

    private function createService(?AccessChecker $accessChecker = null): AccessGrantingRelationGuardService
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->willReturn('http://example.com/error');

        return new AccessGrantingRelationGuardService(
            $accessChecker ?? $this->prophesize(AccessChecker::class)->reveal(),
            new Client403ForbiddenExceptionFactory($urlGenerator->reveal())
        );
    }

    /**
     * @return iterable<string, array{string, list<AccessType>}>
     */
    public static function requiredAccess(): iterable
    {
        yield 'ordinary relation' => ['HAS_TAG', []];
        yield 'unknown access name' => ['HAS_FOO_ACCESS', []];
        yield 'read' => ['HAS_READ_ACCESS', [AccessType::READ]];
        yield 'update' => ['HAS_UPDATE_ACCESS', [AccessType::UPDATE]];
        yield 'delete' => ['HAS_DELETE_ACCESS', [AccessType::DELETE]];
        yield 'create' => ['HAS_CREATE_ACCESS', [AccessType::CREATE]];
        yield 'search' => ['HAS_SEARCH_ACCESS', [AccessType::SEARCH]];
        yield 'owns' => ['OWNS', AccessType::cases()];
        yield 'group' => ['IS_IN_GROUP', AccessType::cases()];
    }

    /**
     * @param list<AccessType> $expected
     */
    #[DataProvider('requiredAccess')]
    public function testRequiredAccessTypes(string $relationType, array $expected): void
    {
        $this->assertSame($expected, $this->createService()->getRequiredAccessTypes($relationType));
    }

    public function testOrdinaryRelationNeedsNoCheck(): void
    {
        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker->hasAccessToElement(Argument::cetera())->shouldNotBeCalled();

        $this->createService($accessChecker->reveal())->assertMayCreate(Uuid::generate(), 'HAS_TAG', Uuid::generate());
        $this->addToAssertionCount(1);
    }

    public function testUserWithOnlyReadAccessCanNotGrantOwnership(): void
    {
        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker->hasAccessToElement(Argument::cetera())->will(
            static fn (array $args): bool => AccessType::READ === $args[2]
        );

        $this->expectException(Client403ForbiddenException::class);
        $this->createService($accessChecker->reveal())->assertMayCreate(Uuid::generate(), 'OWNS', Uuid::generate());
    }

    public function testUserWithTheAccessCanGrantIt(): void
    {
        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker->hasAccessToElement(Argument::cetera())->will(
            static fn (array $args): bool => AccessType::UPDATE === $args[2]
        );

        $this->createService($accessChecker->reveal())->assertMayCreate(Uuid::generate(), 'HAS_UPDATE_ACCESS', Uuid::generate());
        $this->addToAssertionCount(1);
    }
}
