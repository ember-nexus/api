<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\UploadInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AccessChecker;
use App\Service\ElementManager;
use App\Service\UploadCancellationService;
use App\Service\UploadLockService;
use App\Service\UploadService;
use App\Type\AccessType;
use App\Type\NodeElement;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(UploadCancellationService::class)]
class UploadCancellationServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $accessChecker;
    private ObjectProphecy $elementManager;
    private ObjectProphecy $uploadFactory;
    private ObjectProphecy $uploadService;
    private ObjectProphecy $uploadLockService;
    private ObjectProphecy $cypherEntityManager;
    private UploadCancellationService $service;

    protected function setUp(): void
    {
        $this->accessChecker = $this->prophesize(AccessChecker::class);
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementManager->flush()->willReturn($this->elementManager->reveal());
        $this->uploadFactory = $this->prophesize(UploadFactory::class);
        $this->uploadService = $this->prophesize(UploadService::class);
        $this->uploadLockService = $this->prophesize(UploadLockService::class);
        $this->uploadLockService->acquire(Argument::any())->willReturn('token');
        $this->uploadLockService->release(Argument::cetera())->will(function () {});
        $this->cypherEntityManager = $this->prophesize(CypherEntityManager::class);

        $this->service = new UploadCancellationService(
            $this->accessChecker->reveal(),
            $this->elementManager->reveal(),
            $this->uploadFactory->reveal(),
            $this->uploadService->reveal(),
            $this->uploadLockService->reveal(),
            $this->cypherEntityManager->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    private function buildUpload(UuidInterface $id): ObjectProphecy
    {
        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn($id);
        $upload->getUploadOwner()->willReturn(UuidV4::fromString('11111111-1111-4111-8111-111111111111'));
        $upload->getUploadTarget()->willReturn(UuidV4::fromString('22222222-2222-4222-8222-222222222222'));

        return $upload;
    }

    /**
     * @param string[] $uploadIds
     */
    private function configureUploadIds(array $uploadIds): void
    {
        $null = null;
        $result = new SummarizedResult($null, array_map(fn (string $id) => new CypherMap(['u.id' => $id]), $uploadIds));
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::cetera())->willReturn($result);
        $this->cypherEntityManager->getClient()->willReturn($client->reveal());
    }

    public function testUploadsWithoutAccessAreCancelledAndOthersKept(): void
    {
        $lostId = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $keptId = UuidV4::fromString('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $this->configureUploadIds([$lostId->toString(), $keptId->toString()]);

        $lostUpload = $this->buildUpload($lostId);
        $keptUpload = $this->buildUpload($keptId);
        $lostElement = (new NodeElement())->setId($lostId)->setLabel('Upload');
        $keptElement = (new NodeElement())->setId($keptId)->setLabel('Upload');
        $this->elementManager->getElement($lostId)->willReturn($lostElement);
        $this->elementManager->getElement($keptId)->willReturn($keptElement);
        $this->uploadFactory->createUploadFromElement($lostElement)->willReturn($lostUpload->reveal());
        $this->uploadFactory->createUploadFromElement($keptElement)->willReturn($keptUpload->reveal());
        $owner = UuidV4::fromString('11111111-1111-4111-8111-111111111111');
        $target = UuidV4::fromString('22222222-2222-4222-8222-222222222222');
        $this->accessChecker->hasAccessToElement($owner, $target, AccessType::UPDATE)->willReturn(false, true);

        $this->uploadService->deleteUploadAndChunks($lostUpload->reveal())->shouldBeCalledOnce();
        $this->uploadService->deleteUploadAndChunks($keptUpload->reveal())->shouldNotBeCalled();

        $this->assertSame(1, $this->service->cancelUploadsWithoutAccess());
    }

    public function testMissingAndMalformedUploadElementsAreSkipped(): void
    {
        $missingId = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $malformedId = UuidV4::fromString('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $this->configureUploadIds([$missingId->toString(), $malformedId->toString()]);
        $malformedElement = (new NodeElement())->setId($malformedId)->setLabel('Upload');
        $this->elementManager->getElement($missingId)->willReturn(null);
        $this->elementManager->getElement($malformedId)->willReturn($malformedElement);
        $this->uploadFactory->createUploadFromElement($malformedElement)->willThrow(new Exception('malformed'));
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();

        $this->assertSame(0, $this->service->cancelUploadsWithoutAccess());
    }

    public function testLockedUploadIsNotCancelled(): void
    {
        $id = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $upload = $this->buildUpload($id);
        $this->uploadLockService->acquire($id)->willReturn(null);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $this->uploadLockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->assertFalse($this->service->cancelUpload($upload->reveal()));
    }

    public function testCancelUploadReloadsUploadUnderLockAndDeletesChunksAndNode(): void
    {
        $id = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $upload = $this->buildUpload($id);
        $freshUpload = $this->buildUpload($id);
        $element = (new NodeElement())->setId($id)->setLabel('Upload');
        $this->elementManager->getElement($id)->willReturn($element);
        $this->uploadFactory->createUploadFromElement($element)->willReturn($freshUpload->reveal());
        $this->uploadService->deleteUploadAndChunks($freshUpload->reveal())->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->uploadLockService->release($id, 'token')->shouldBeCalledOnce();

        $this->assertTrue($this->service->cancelUpload($upload->reveal()));
    }

    public function testCancelUploadOfAlreadyDeletedUploadSucceedsWithoutDeleting(): void
    {
        $id = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $upload = $this->buildUpload($id);
        $this->elementManager->getElement($id)->willReturn(null);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $this->uploadLockService->release($id, 'token')->shouldBeCalledOnce();

        $this->assertTrue($this->service->cancelUpload($upload->reveal()));
    }
}
