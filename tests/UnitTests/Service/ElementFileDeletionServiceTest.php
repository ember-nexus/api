<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\RelationElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Service\ElementFileDeletionService;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\S3Service;
use App\Type\NodeElement;
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
#[CoversClass(ElementFileDeletionService::class)]
class ElementFileDeletionServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ElementManager>
     */
    private ObjectProphecy $elementManager;
    /**
     * @var ObjectProphecy<ElementService>
     */
    private ObjectProphecy $elementService;
    /**
     * @var ObjectProphecy<FileOperationFactory>
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
    private ElementFileDeletionService $service;

    protected function setUp(): void
    {
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementService = $this->prophesize(ElementService::class);
        $this->fileOperationFactory = $this->prophesize(FileOperationFactory::class);
        $this->s3Service = $this->prophesize(S3Service::class);
        $this->client = $this->prophesize(ClientInterface::class);
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($this->client->reveal());

        $this->service = new ElementFileDeletionService(
            $this->elementManager->reveal(),
            $this->elementService->reveal(),
            $this->fileOperationFactory->reveal(),
            $this->s3Service->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    private function buildNode(UuidInterface $id): NodeElement
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
        $node = $this->buildNode(UuidV4::uuid4());
        $this->client->runStatement(Argument::any())->willReturn($this->buildAttachedRelationsResult());
        $this->elementService->hasFile($node)->willReturn(false);
        $this->fileOperationFactory->createFileOperationFromElement(Argument::any())->shouldNotBeCalled();

        $this->assertSame([], $this->service->getFileOperationsForDeletionOfElement($node));
    }

    public function testNodeWithFileHasFileOperation(): void
    {
        $node = $this->buildNode(UuidV4::uuid4());
        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $this->client->runStatement(Argument::any())->willReturn($this->buildAttachedRelationsResult());
        $this->elementService->hasFile($node)->willReturn(true);
        $this->fileOperationFactory->createFileOperationFromElement($node)->willReturn($fileOperation);

        $this->assertSame([$fileOperation], $this->service->getFileOperationsForDeletionOfElement($node));
    }

    public function testFilesOfAttachedRelationsAreIncludedForNodes(): void
    {
        $node = $this->buildNode(UuidV4::uuid4());
        $relationId = UuidV4::uuid4();
        $relation = $this->prophesize(RelationElementInterface::class)->reveal();
        $relationFileOperation = $this->prophesize(FileOperationInterface::class)->reveal();

        $this->client->runStatement(Argument::any())->willReturn($this->buildAttachedRelationsResult($relationId->toString()));
        $this->elementManager->getElementOrFail(Argument::that(fn (UuidInterface $id) => $id->equals($relationId)))->willReturn($relation);
        $this->elementService->hasFile($node)->willReturn(false);
        $this->elementService->hasFile($relation)->willReturn(true);
        $this->fileOperationFactory->createFileOperationFromElement($relation)->willReturn($relationFileOperation);

        $this->assertSame([$relationFileOperation], $this->service->getFileOperationsForDeletionOfElement($node));
    }

    public function testRelationDoesNotQueryForAttachedRelations(): void
    {
        $relation = $this->prophesize(RelationElementInterface::class)->reveal();
        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $this->client->runStatement(Argument::any())->shouldNotBeCalled();
        $this->elementService->hasFile($relation)->willReturn(true);
        $this->fileOperationFactory->createFileOperationFromElement($relation)->willReturn($fileOperation);

        $this->assertSame([$fileOperation], $this->service->getFileOperationsForDeletionOfElement($relation));
    }

    public function testDeleteFilesDeletesEveryFile(): void
    {
        $first = $this->prophesize(FileOperationInterface::class)->reveal();
        $second = $this->prophesize(FileOperationInterface::class)->reveal();

        $this->s3Service->deleteFile($first)->shouldBeCalledOnce();
        $this->s3Service->deleteFile($second)->shouldBeCalledOnce();

        $this->service->deleteFiles([$first, $second]);
    }
}
