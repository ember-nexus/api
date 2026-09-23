<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\RelationElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\UploadInterface;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Service\ElementManager;
use App\Service\S3Service;
use App\Service\UploadService;
use App\Type\NodeElement;
use DateTime;
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
#[CoversClass(UploadService::class)]
class UploadServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return array{0: UploadService, 1: ObjectProphecy<ElementManager>, 2: ObjectProphecy<Server500LogicErrorExceptionFactory>, 3: ObjectProphecy<FileOperationFactory>, 4: ObjectProphecy<S3Service>, 5: ObjectProphecy<UploadFactory>, 6: ObjectProphecy<CypherEntityManager>}
     */
    private function buildService(
        ?ObjectProphecy $elementManager = null,
        ?ObjectProphecy $server500LogicErrorExceptionFactory = null,
        ?ObjectProphecy $fileOperationFactory = null,
        ?ObjectProphecy $s3Service = null,
        ?ObjectProphecy $uploadFactory = null,
        ?ObjectProphecy $cypherEntityManager = null,
    ): array {
        $elementManager ??= $this->prophesize(ElementManager::class);
        $server500LogicErrorExceptionFactory ??= $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $fileOperationFactory ??= $this->prophesize(FileOperationFactory::class);
        $s3Service ??= $this->prophesize(S3Service::class);
        $uploadFactory ??= $this->prophesize(UploadFactory::class);
        $cypherEntityManager ??= $this->prophesize(CypherEntityManager::class);

        $service = new UploadService(
            $elementManager->reveal(),
            $server500LogicErrorExceptionFactory->reveal(),
            $fileOperationFactory->reveal(),
            $s3Service->reveal(),
            $uploadFactory->reveal(),
            $cypherEntityManager->reveal(),
        );

        return [$service, $elementManager, $server500LogicErrorExceptionFactory, $fileOperationFactory, $s3Service, $uploadFactory, $cypherEntityManager];
    }

    /**
     * @return ObjectProphecy<UploadInterface>
     */
    private function buildUpload(
        ?UuidInterface $id = null,
        ?int $uploadLength = 1000,
        int $uploadOffset = 0,
        bool $uploadComplete = false,
        ?UuidInterface $uploadTarget = null,
        int $alreadyUploadedChunks = 0,
        ?UuidInterface $uploadOwner = null,
        string $extension = 'bin',
        ?string $hashState = null,
    ): ObjectProphecy {
        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn($id ?? UuidV4::uuid4());
        $upload->getUploadLength()->willReturn($uploadLength);
        $upload->getUploadOffset()->willReturn($uploadOffset);
        $upload->isUploadComplete()->willReturn($uploadComplete);
        $upload->getUploadTarget()->willReturn($uploadTarget ?? UuidV4::uuid4());
        $upload->getAlreadyUploadedChunks()->willReturn($alreadyUploadedChunks);
        $upload->getUploadOwner()->willReturn($uploadOwner ?? UuidV4::uuid4());
        $upload->getExtension()->willReturn($extension);
        $upload->getExpires()->willReturn(new DateTime('2099-01-01'));
        $upload->getHashState()->willReturn($hashState);

        return $upload;
    }

    public function testMergeUploadElementCreatesNewElementWhenNoneExists(): void
    {
        $id = UuidV4::uuid4();
        $uploadTarget = UuidV4::uuid4();
        $uploadOwner = UuidV4::uuid4();
        $upload = $this->buildUpload(id: $id, uploadTarget: $uploadTarget, uploadOwner: $uploadOwner, extension: 'png', hashState: 'abc');

        $capturedElement = null;
        [$service, $elementManager] = $this->buildService();
        $elementManager->getElement($id)->willReturn(null);
        $elementManager->merge(Argument::that(function (NodeElement $element) use (&$capturedElement) {
            $capturedElement = $element;

            return true;
        }))->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->mergeUploadElement($upload->reveal());

        $mergedElement = $capturedElement;
        $this->assertInstanceOf(NodeElement::class, $mergedElement);
        $this->assertTrue($mergedElement->getId()?->equals($id));
        $this->assertSame('Upload', $mergedElement->getLabel());
        $this->assertSame(
            [
                'uploadLength' => 1000,
                'uploadOffset' => 0,
                'uploadComplete' => false,
                'uploadTarget' => $uploadTarget->toString(),
                'alreadyUploadedChunks' => 0,
                'uploadOwner' => $uploadOwner->toString(),
                'extension' => 'png',
                'expires' => $mergedElement->getProperty('expires'),
                'hashState' => 'abc',
            ],
            $mergedElement->getProperties()
        );
    }

    public function testMergeUploadElementUpdatesExistingUploadElement(): void
    {
        $id = UuidV4::uuid4();
        $existingElement = (new NodeElement())->setId($id)->setLabel('Upload');
        $upload = $this->buildUpload(id: $id, uploadLength: null, uploadOffset: 42);

        [$service, $elementManager] = $this->buildService();
        $elementManager->getElement($id)->willReturn($existingElement);
        $elementManager->merge($existingElement)->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->mergeUploadElement($upload->reveal());

        $this->assertNull($existingElement->getProperty('uploadLength'));
        $this->assertSame(42, $existingElement->getProperty('uploadOffset'));
    }

    public function testMergeUploadElementThrowsWhenExistingElementIsNotANode(): void
    {
        $id = UuidV4::uuid4();
        $relationElement = $this->prophesize(RelationElementInterface::class)->reveal();
        $upload = $this->buildUpload(id: $id);

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        [$service, $elementManager, $server500LogicErrorExceptionFactory] = $this->buildService();
        $elementManager->getElement($id)->willReturn($relationElement);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::containingString('Expected upload element to be a node'))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $this->expectException(Server500LogicErrorException::class);
        $service->mergeUploadElement($upload->reveal());
    }

    public function testMergeUploadElementThrowsWhenExistingElementHasWrongLabel(): void
    {
        $id = UuidV4::uuid4();
        $existingElement = (new NodeElement())->setId($id)->setLabel('SomethingElse');
        $upload = $this->buildUpload(id: $id);

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        [$service, $elementManager, $server500LogicErrorExceptionFactory] = $this->buildService();
        $elementManager->getElement($id)->willReturn($existingElement);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::containingString("not 'SomethingElse'"))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $this->expectException(Server500LogicErrorException::class);
        $service->mergeUploadElement($upload->reveal());
    }

    public function testMergeUploadElementThrowsWhenExistingElementHasNullLabel(): void
    {
        $id = UuidV4::uuid4();
        $existingElement = (new NodeElement())->setId($id);
        $upload = $this->buildUpload(id: $id);

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        [$service, $elementManager, $server500LogicErrorExceptionFactory] = $this->buildService();
        $elementManager->getElement($id)->willReturn($existingElement);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::containingString("not 'null'"))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $this->expectException(Server500LogicErrorException::class);
        $service->mergeUploadElement($upload->reveal());
    }

    public function testDeleteUploadDeletesElement(): void
    {
        $id = UuidV4::uuid4();
        $upload = $this->buildUpload(id: $id);
        $element = (new NodeElement())->setId($id)->setLabel('Upload');

        [$service, $elementManager] = $this->buildService();
        $elementManager->getElementOrFail($id)->willReturn($element)->shouldBeCalledOnce();
        $elementManager->delete($element)->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->deleteUpload($upload->reveal());
    }

    public function testDeleteUploadAndChunksDeletesOneChunkWhenNoneUploadedYet(): void
    {
        $id = UuidV4::uuid4();
        $upload = $this->buildUpload(id: $id, alreadyUploadedChunks: 0);
        $element = (new NodeElement())->setId($id)->setLabel('Upload');

        [$service, $elementManager, , $fileOperationFactory, $s3Service] = $this->buildService();
        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $fileOperationFactory->createFileOperationFromUpload($upload->reveal(), 0)->willReturn($fileOperation)->shouldBeCalledOnce();
        $s3Service->deleteFile($fileOperation)->shouldBeCalledOnce();
        $elementManager->getElementOrFail($id)->willReturn($element);
        $elementManager->delete($element)->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->deleteUploadAndChunks($upload->reveal());
    }

    public function testDeleteUploadAndChunksDeletesAllChunksInclusive(): void
    {
        $id = UuidV4::uuid4();
        $upload = $this->buildUpload(id: $id, alreadyUploadedChunks: 2);
        $element = (new NodeElement())->setId($id)->setLabel('Upload');

        [$service, $elementManager, , $fileOperationFactory, $s3Service] = $this->buildService();
        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        foreach ([0, 1, 2] as $chunk) {
            $fileOperationFactory->createFileOperationFromUpload($upload->reveal(), $chunk)->willReturn($fileOperation)->shouldBeCalledOnce();
        }
        $s3Service->deleteFile($fileOperation)->shouldBeCalledTimes(3);
        $elementManager->getElementOrFail($id)->willReturn($element);
        $elementManager->delete($element)->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->deleteUploadAndChunks($upload->reveal());
    }

    private function buildSummarizedResultOf(CypherMap ...$maps): SummarizedResult
    {
        $summary = null;

        return new SummarizedResult($summary, $maps);
    }

    public function testDeleteUploadsTargetingDoesNothingWhenNoUploadsFound(): void
    {
        $elementId = UuidV4::uuid4();

        [$service, $elementManager, , , , , $cypherEntityManager] = $this->buildService();
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->willReturn($this->buildSummarizedResultOf());
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $elementManager->getElement(Argument::any())->shouldNotBeCalled();

        $service->deleteUploadsTargeting($elementId);
        $this->addToAssertionCount(1);
    }

    public function testDeleteUploadsTargetingSkipsUploadsAlreadyGone(): void
    {
        $elementId = UuidV4::uuid4();
        $uploadId = UuidV4::uuid4();

        [$service, $elementManager, , , , , $cypherEntityManager] = $this->buildService();
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->willReturn($this->buildSummarizedResultOf(new CypherMap(['u.id' => $uploadId->toString()])));
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $elementManager->getElement(Argument::that(fn (UuidInterface $id) => $id->equals($uploadId)))->willReturn(null);
        $elementManager->delete(Argument::any())->shouldNotBeCalled();

        $service->deleteUploadsTargeting($elementId);
        $this->addToAssertionCount(1);
    }

    public function testDeleteUploadsTargetingSkipsUploadsThatFailToParse(): void
    {
        $elementId = UuidV4::uuid4();
        $uploadId = UuidV4::uuid4();
        $uploadElement = (new NodeElement())->setId($uploadId)->setLabel('Upload');

        [$service, $elementManager, , , , $uploadFactory, $cypherEntityManager] = $this->buildService();
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->willReturn($this->buildSummarizedResultOf(new CypherMap(['u.id' => $uploadId->toString()])));
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $elementManager->getElement(Argument::that(fn (UuidInterface $id) => $id->equals($uploadId)))->willReturn($uploadElement);
        $uploadFactory->createUploadFromElement($uploadElement)->willThrow(new Exception('bad upload'));
        $elementManager->delete(Argument::any())->shouldNotBeCalled();

        $service->deleteUploadsTargeting($elementId);
        $this->addToAssertionCount(1);
    }

    public function testDeleteUploadsTargetingDeletesFoundUploadsAndTheirChunks(): void
    {
        $elementId = UuidV4::uuid4();
        $uploadId = UuidV4::uuid4();
        $uploadElement = (new NodeElement())->setId($uploadId)->setLabel('Upload');
        $upload = $this->buildUpload(id: $uploadId, alreadyUploadedChunks: 1)->reveal();

        [$service, $elementManager, , $fileOperationFactory, $s3Service, $uploadFactory, $cypherEntityManager] = $this->buildService();
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->willReturn($this->buildSummarizedResultOf(new CypherMap(['u.id' => $uploadId->toString()])));
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $elementManager->getElement(Argument::that(fn (UuidInterface $id) => $id->equals($uploadId)))->willReturn($uploadElement);
        $uploadFactory->createUploadFromElement($uploadElement)->willReturn($upload);

        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $fileOperationFactory->createFileOperationFromUpload($upload, Argument::any())->willReturn($fileOperation);
        $s3Service->deleteFile($fileOperation)->shouldBeCalledTimes(2);

        $elementManager->getElementOrFail($uploadId)->willReturn($uploadElement);
        $elementManager->delete($uploadElement)->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->deleteUploadsTargeting($elementId);
    }
}
