<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command\Cron;

use App\Command\Cron\DeleteExpiredUploadsCommand;
use App\Contract\S3\FileOperationInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Service\ElementManager;
use App\Service\S3Service;
use App\Service\UploadService;
use App\Type\NodeElement;
use App\Type\Upload;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(DeleteExpiredUploadsCommand::class)]
class DeleteExpiredUploadsCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array<int, array<string, mixed>> $expiredUploadRows
     */
    private function buildCommand(
        bool $isCronDisabled = false,
        array $expiredUploadRows = [],
        ?ElementManager $elementManager = null,
        ?UploadFactory $uploadFactory = null,
        ?UploadService $uploadService = null,
        ?FileOperationFactory $fileOperationFactory = null,
        ?S3Service $s3Service = null,
    ): DeleteExpiredUploadsCommand {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn($isCronDisabled);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        if ($isCronDisabled) {
            $cypherEntityManager->getClient()->shouldNotBeCalled();
        } else {
            $client = $this->prophesize(ClientInterface::class);
            $summaryReference = null;
            $rows = array_map(static fn (array $row) => new CypherMap($row), $expiredUploadRows);
            $client->runStatement(Argument::any())->willReturn(new SummarizedResult($summaryReference, $rows));
            $cypherEntityManager->getClient()->willReturn($client->reveal());
        }

        return new DeleteExpiredUploadsCommand(
            $bag->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager ?? $this->prophesize(ElementManager::class)->reveal(),
            $uploadFactory ?? $this->prophesize(UploadFactory::class)->reveal(),
            $uploadService ?? $this->prophesize(UploadService::class)->reveal(),
            $fileOperationFactory ?? $this->prophesize(FileOperationFactory::class)->reveal(),
            $s3Service ?? $this->prophesize(S3Service::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
    }

    public function testCommandStopsEarlyIfCronIsDisabled(): void
    {
        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->shouldNotBeCalled();

        $command = $this->buildCommand(isCronDisabled: true, elementManager: $elementManager->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Cron is disabled', $commandTester->getDisplay());
    }

    public function testCommandThrowsIfCronDisabledParameterIsNotBoolean(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn('not-a-boolean');

        $command = new DeleteExpiredUploadsCommand(
            $bag->reveal(),
            $this->prophesize(CypherEntityManager::class)->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->prophesize(UploadFactory::class)->reveal(),
            $this->prophesize(UploadService::class)->reveal(),
            $this->prophesize(FileOperationFactory::class)->reveal(),
            $this->prophesize(S3Service::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->expectException(LogicException::class);
        (new CommandTester($command))->execute([]);
    }

    public function testCommandDoesNothingIfNoUploadsAreExpired(): void
    {
        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->shouldNotBeCalled();
        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUpload(Argument::any())->shouldNotBeCalled();

        $command = $this->buildCommand(
            expiredUploadRows: [],
            elementManager: $elementManager->reveal(),
            uploadService: $uploadService->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('No expired uploads found.', $commandTester->getDisplay());
    }

    public function testCommandDeletesSingleExpiredUploadIncludingItsChunks(): void
    {
        $uploadId = Uuid::fromString('7d7c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d1');
        $uploadTarget = Uuid::fromString('8f8c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d2');
        $uploadOwner = Uuid::fromString('9a9c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d3');

        $upload = new Upload(
            $uploadId,
            null,
            5 * 1024 * 1024,
            false,
            $uploadTarget,
            2,
            $uploadOwner,
            'bin',
            (new DateTime())->modify('-1 hour')
        );

        $uploadElement = (new NodeElement())->setId($uploadId)->setLabel('Upload');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $uploadId->toString()))
            ->shouldBeCalledOnce()
            ->willReturn($uploadElement);
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $uploadFactory = $this->prophesize(UploadFactory::class);
        $uploadFactory->createUploadFromElement(Argument::is($uploadElement))->willReturn($upload);

        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $fileOperationFactory = $this->prophesize(FileOperationFactory::class);
        $fileOperationFactory->createFileOperationFromUpload(Argument::is($upload), Argument::any())
            ->shouldBeCalledTimes(3) // chunks 0, 1 and 2 (alreadyUploadedChunks + 1)
            ->willReturn($fileOperation);

        $s3Service = $this->prophesize(S3Service::class);
        $s3Service->deleteFile(Argument::is($fileOperation))->shouldBeCalledTimes(3);

        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUpload(Argument::is($upload))->shouldBeCalledOnce();

        $command = $this->buildCommand(
            expiredUploadRows: [['u.id' => $uploadId->toString()]],
            elementManager: $elementManager->reveal(),
            uploadFactory: $uploadFactory->reveal(),
            uploadService: $uploadService->reveal(),
            fileOperationFactory: $fileOperationFactory->reveal(),
            s3Service: $s3Service->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 1 expired upload(s).', $commandTester->getDisplay());
    }

    public function testCommandSkipsUploadWhichWasAlreadyDeletedInTheMeantime(): void
    {
        $uploadId = Uuid::fromString('1c1c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d1');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->willReturn(null);

        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUpload(Argument::any())->shouldNotBeCalled();

        $s3Service = $this->prophesize(S3Service::class);
        $s3Service->deleteFile(Argument::any())->shouldNotBeCalled();

        $command = $this->buildCommand(
            expiredUploadRows: [['u.id' => $uploadId->toString()]],
            elementManager: $elementManager->reveal(),
            uploadService: $uploadService->reveal(),
            s3Service: $s3Service->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 1 expired upload(s).', $commandTester->getDisplay());
    }

    public function testCommandDeletesMultipleExpiredUploads(): void
    {
        $uploadId1 = Uuid::fromString('2d2c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d1');
        $uploadId2 = Uuid::fromString('3e3c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d2');

        $buildUpload = fn ($id) => new Upload($id, null, 0, false, $id, 0, $id, 'bin', (new DateTime())->modify('-1 hour'));

        $uploadElement1 = (new NodeElement())->setId($uploadId1)->setLabel('Upload');
        $uploadElement2 = (new NodeElement())->setId($uploadId2)->setLabel('Upload');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $uploadId1->toString()))->willReturn($uploadElement1);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $uploadId2->toString()))->willReturn($uploadElement2);
        $elementManager->flush()->willReturn($elementManager->reveal());

        $uploadFactory = $this->prophesize(UploadFactory::class);
        $uploadFactory->createUploadFromElement(Argument::is($uploadElement1))->willReturn($buildUpload($uploadId1));
        $uploadFactory->createUploadFromElement(Argument::is($uploadElement2))->willReturn($buildUpload($uploadId2));

        $fileOperationFactory = $this->prophesize(FileOperationFactory::class);
        $fileOperationFactory->createFileOperationFromUpload(Argument::any(), Argument::any())
            ->willReturn($this->prophesize(FileOperationInterface::class)->reveal());

        $s3Service = $this->prophesize(S3Service::class);
        $s3Service->deleteFile(Argument::any())->shouldBeCalledTimes(2);

        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUpload(Argument::any())->shouldBeCalledTimes(2);

        $command = $this->buildCommand(
            expiredUploadRows: [
                ['u.id' => $uploadId1->toString()],
                ['u.id' => $uploadId2->toString()],
            ],
            elementManager: $elementManager->reveal(),
            uploadFactory: $uploadFactory->reveal(),
            uploadService: $uploadService->reveal(),
            fileOperationFactory: $fileOperationFactory->reveal(),
            s3Service: $s3Service->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString('Deleted 2 expired upload(s).', $commandTester->getDisplay());
    }
}
