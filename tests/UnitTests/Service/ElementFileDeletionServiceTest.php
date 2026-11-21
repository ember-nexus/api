<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\RelationElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Service\ElementFileDeletionService;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\ElementTypeService;
use App\Service\S3Service;
use App\Tests\UnitTests\AssertLoggerTrait;
use App\Type\NodeElement;
use App\Type\Uuid;
use Beste\Psr\Log\TestLogger;
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
use RuntimeException;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(ElementFileDeletionService::class)]
class ElementFileDeletionServiceTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    /**
     * @var ObjectProphecy<ElementManager>
     */
    private ObjectProphecy $elementManager;
    /**
     * @var ObjectProphecy<ElementService>
     */
    private ObjectProphecy $elementService;
    /**
     * @var ObjectProphecy<S3OperationFactory>
     */
    private ObjectProphecy $fileOperationFactory;
    /**
     * @var ObjectProphecy<S3Service>
     */
    private ObjectProphecy $s3Service;
    /**
     * @var ObjectProphecy<ClientInterface>
     */
    private ObjectProphecy $client;
    private TestLogger $logger;
    private ElementFileDeletionService $service;

    protected function setUp(): void
    {
        $this->setUpService('Data');
    }

    private function setUpService(?string $nodeLabel): void
    {
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementService = $this->prophesize(ElementService::class);
        $this->fileOperationFactory = $this->prophesize(S3OperationFactory::class);
        $this->s3Service = $this->prophesize(S3Service::class);
        $this->logger = TestLogger::create();
        $this->client = $this->prophesize(ClientInterface::class);
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($this->client->reveal());

        $this->service = new ElementFileDeletionService(
            $this->elementManager->reveal(),
            $this->elementService->reveal(),
            $this->fileOperationFactory->reveal(),
            $this->s3Service->reveal(),
            $cypherEntityManager->reveal(),
            $this->buildElementTypeService($nodeLabel),
            $this->logger,
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    private function buildElementTypeService(?string $nodeLabel = 'Data'): ElementTypeService
    {
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolveNodeLabel(Argument::any())->willReturn($nodeLabel);

        return $elementTypeService->reveal();
    }

    private function buildNode(Uuid $id): NodeElement
    {
        $node = new NodeElement();
        $node->setId($id);

        return $node;
    }

    private function buildAttachedRelationsResult(string ...$relationIds): SummarizedResult
    {
        $summary = null;

        return new SummarizedResult($summary, array_map(fn (string $id) => new CypherMap(['r.id' => $id]), $relationIds));
    }

    public function testNodeWithoutFileAndWithoutAttachedRelationsHasNoFileOperations(): void
    {
        $node = $this->buildNode(Uuid::generate());
        $this->client->runStatement(Argument::any())->willReturn($this->buildAttachedRelationsResult());
        $this->elementService->hasFile($node)->willReturn(false);
        $this->fileOperationFactory->createFileOperationFromElement(Argument::any())->shouldNotBeCalled();

        $this->assertSame([], $this->service->getFileOperationsForDeletionOfElement($node));
    }

    public function testNodeWithFileHasFileOperation(): void
    {
        $nodeId = Uuid::generate();
        $node = $this->buildNode($nodeId);
        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $this->client->runStatement(Argument::any())->willReturn($this->buildAttachedRelationsResult());
        $this->elementService->hasFile($node)->willReturn(true);
        $this->fileOperationFactory->createFileOperationFromElement($node)->willReturn($fileOperation);

        $this->assertSame(
            [['elementId' => $nodeId, 'operation' => $fileOperation]],
            $this->service->getFileOperationsForDeletionOfElement($node)
        );
    }

    public function testFilesOfAttachedRelationsAreIncludedForNodes(): void
    {
        $node = $this->buildNode(Uuid::generate());
        $relationId = Uuid::generate();
        $relation = $this->prophesize(RelationElementInterface::class);
        $relation->getId()->willReturn($relationId);
        $relation = $relation->reveal();
        $relationFileOperation = $this->prophesize(FileOperationInterface::class)->reveal();

        $this->client->runStatement(Argument::any())->willReturn($this->buildAttachedRelationsResult($relationId->toString()));
        $this->elementManager->getElementOrFail(Argument::that(fn (Uuid $id) => $id->equals($relationId)))->willReturn($relation);
        $this->elementService->hasFile($node)->willReturn(false);
        $this->elementService->hasFile($relation)->willReturn(true);
        $this->fileOperationFactory->createFileOperationFromElement($relation)->willReturn($relationFileOperation);

        $this->assertSame(
            [['elementId' => $relationId, 'operation' => $relationFileOperation]],
            $this->service->getFileOperationsForDeletionOfElement($node)
        );
    }

    public function testNodeWithUnresolvedLabelDoesNotQueryForAttachedRelations(): void
    {
        $this->setUpService(null);
        $node = $this->buildNode(Uuid::generate());
        $this->client->runStatement(Argument::any())->shouldNotBeCalled();
        $this->elementService->hasFile($node)->willReturn(false);

        $this->assertSame([], $this->service->getFileOperationsForDeletionOfElement($node));
    }

    public function testAttachedRelationsQueryUsesTheEscapedResolvedLabel(): void
    {
        $this->setUpService('Da`ta');
        $node = $this->buildNode(Uuid::generate());
        $this->client->runStatement(Argument::that(
            fn (Statement $statement) => str_contains($statement->getText(), 'MATCH (:`Da``ta` {id: $nodeId})-[r]-()')
        ))->shouldBeCalledOnce()->willReturn($this->buildAttachedRelationsResult());
        $this->elementService->hasFile($node)->willReturn(false);

        $this->service->getFileOperationsForDeletionOfElement($node);
    }

    public function testRelationDoesNotQueryForAttachedRelations(): void
    {
        $relationId = Uuid::generate();
        $relation = $this->prophesize(RelationElementInterface::class);
        $relation->getId()->willReturn($relationId);
        $relation = $relation->reveal();
        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $this->client->runStatement(Argument::any())->shouldNotBeCalled();
        $this->elementService->hasFile($relation)->willReturn(true);
        $this->fileOperationFactory->createFileOperationFromElement($relation)->willReturn($fileOperation);

        $this->assertSame(
            [['elementId' => $relationId, 'operation' => $fileOperation]],
            $this->service->getFileOperationsForDeletionOfElement($relation)
        );
    }

    public function testDeleteFilesDeletesEveryFile(): void
    {
        $firstId = Uuid::generate();
        $secondId = Uuid::generate();
        $first = $this->prophesize(FileOperationInterface::class)->reveal();
        $second = $this->prophesize(FileOperationInterface::class)->reveal();

        $this->s3Service->deleteFile($first)->shouldBeCalledOnce()->willReturn(true);
        $this->s3Service->deleteFile($second)->shouldBeCalledOnce()->willReturn(true);

        $this->service->deleteFiles([
            ['elementId' => $firstId, 'operation' => $first],
            ['elementId' => $secondId, 'operation' => $second],
        ]);
    }

    public function testDeleteFilesLogsFailuresAndContinues(): void
    {
        $failing = $this->prophesize(FileOperationInterface::class);
        $failing->getKey()->willReturn('some-key.txt');
        $failing->getBucket()->willReturn('some-bucket');
        $failingOperation = $failing->reveal();
        $second = $this->prophesize(FileOperationInterface::class)->reveal();

        $this->s3Service->deleteFile($failingOperation)->willThrow(new RuntimeException('S3 is down'));
        $this->s3Service->deleteFile($second)->shouldBeCalledOnce()->willReturn(true);

        $this->service->deleteFiles([
            ['elementId' => Uuid::generate(), 'operation' => $failingOperation],
            ['elementId' => Uuid::generate(), 'operation' => $second],
        ]);

        $this->assertLogHappened(
            $this->logger,
            'error',
            "Unable to delete file 'some-key.txt' from bucket 'some-bucket'",
            ['bucket' => 'some-bucket', 'key' => 'some-key.txt']
        );
    }

    public function testDeleteFileDoesNotThrow(): void
    {
        $failing = $this->prophesize(FileOperationInterface::class);
        $failing->getKey()->willReturn('k');
        $failing->getBucket()->willReturn('b');
        $operation = $failing->reveal();
        $this->s3Service->deleteFile($operation)->willThrow(new RuntimeException('boom'));

        $this->service->deleteFile($operation);

        $this->assertCount(1, $this->logger->records->all());
    }

    public function testDeleteFileLogsNoticeWhenFileDoesNotExist(): void
    {
        $elementId = Uuid::generate();
        $operation = $this->prophesize(FileOperationInterface::class)->reveal();
        $this->s3Service->deleteFile($operation)->willReturn(false);

        $this->service->deleteFile($operation, $elementId);

        $this->assertLogHappened(
            $this->logger,
            'notice',
            sprintf("To be deleted element with id '%s' does not have a file in S3.", $elementId->toString())
        );
    }

    public function testDeleteFileWithoutElementIdDoesNotLogWhenFileDoesNotExist(): void
    {
        $operation = $this->prophesize(FileOperationInterface::class)->reveal();
        $this->s3Service->deleteFile($operation)->willReturn(false);

        $this->service->deleteFile($operation);

        $this->assertCount(0, $this->logger->records->all());
    }

    public function testDeletePreviousFileAfterReplaceDeletesTheOldKey(): void
    {
        $operation = $this->prophesize(UploadFileOperationInterface::class);
        $operation->getPreviousStorageKey()->willReturn('some-key.old');
        $operation->getStorageKey()->willReturn('some-key.new');
        $operation->getStorageBucket()->willReturn('some-bucket');

        $this->s3Service->deleteFile(Argument::that(
            fn (FileOperationInterface $fileOperation) => 'some-bucket' === $fileOperation->getBucket() && 'some-key.old' === $fileOperation->getKey()
        ))->shouldBeCalledOnce();

        $this->service->deletePreviousFileAfterReplace($operation->reveal());
    }

    public function testDeletePreviousFileAfterReplaceKeepsTheFileIfKeyIsUnchangedOrThereIsNoPreviousFile(): void
    {
        $sameKey = $this->prophesize(MergeFileChunksOperationInterface::class);
        $sameKey->getPreviousStorageKey()->willReturn('some-key');
        $sameKey->getStorageKey()->willReturn('some-key');
        $sameKey->getStorageBucket()->willReturn('some-bucket');
        $noPrevious = $this->prophesize(MergeFileChunksOperationInterface::class);
        $noPrevious->getPreviousStorageKey()->willReturn(null);

        $this->s3Service->deleteFile(Argument::any())->shouldNotBeCalled();

        $this->service->deletePreviousFileAfterReplace($sameKey->reveal());
        $this->service->deletePreviousFileAfterReplace($noPrevious->reveal());
    }

    public function testDeletePreviousFileAfterReplaceLogsFailureAndDoesNotThrow(): void
    {
        $operation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $operation->getPreviousStorageKey()->willReturn('some-key.old');
        $operation->getStorageKey()->willReturn('some-key.new');
        $operation->getStorageBucket()->willReturn('some-bucket');
        $this->s3Service->deleteFile(Argument::any())->willThrow(new RuntimeException('S3 is down'));

        $this->service->deletePreviousFileAfterReplace($operation->reveal());

        $this->assertLogHappened(
            $this->logger,
            'error',
            "Unable to delete file 'some-key.old' from bucket 'some-bucket'",
            ['bucket' => 'some-bucket', 'key' => 'some-key.old']
        );
    }
}
