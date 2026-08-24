<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command\Cron;

use App\Command\Cron\DeleteExpiredUploadsCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Service\ElementManager;
use App\Service\UploadService;
use App\Type\NodeElement;
use App\Type\Upload;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
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
        int $expiredUploadCanBeDeletedAfterExpirationInSeconds = 3600,
        ?ClientInterface $client = null,
    ): DeleteExpiredUploadsCommand {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn($isCronDisabled);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileExpiredUploadCanBeDeletedAfterExpirationInSeconds()
            ->willReturn($expiredUploadCanBeDeletedAfterExpirationInSeconds);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        if ($isCronDisabled) {
            $cypherEntityManager->getClient()->shouldNotBeCalled();
        } elseif (null !== $client) {
            $cypherEntityManager->getClient()->willReturn($client);
        } else {
            $clientProphecy = $this->prophesize(ClientInterface::class);
            $summaryReference = null;
            $rows = array_map(static fn (array $row) => new CypherMap($row), $expiredUploadRows);
            $clientProphecy->runStatement(Argument::any())->willReturn(new SummarizedResult($summaryReference, $rows));
            $cypherEntityManager->getClient()->willReturn($clientProphecy->reveal());
        }

        return new DeleteExpiredUploadsCommand(
            $bag->reveal(),
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager ?? $this->prophesize(ElementManager::class)->reveal(),
            $uploadFactory ?? $this->prophesize(UploadFactory::class)->reveal(),
            $uploadService ?? $this->prophesize(UploadService::class)->reveal(),
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
            $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $this->prophesize(CypherEntityManager::class)->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->prophesize(UploadFactory::class)->reveal(),
            $this->prophesize(UploadService::class)->reveal(),
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
        $uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();

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

        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUploadAndChunks(Argument::is($upload))->shouldBeCalledOnce();

        $command = $this->buildCommand(
            expiredUploadRows: [['u.id' => $uploadId->toString()]],
            elementManager: $elementManager->reveal(),
            uploadFactory: $uploadFactory->reveal(),
            uploadService: $uploadService->reveal()
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
        $uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();

        $command = $this->buildCommand(
            expiredUploadRows: [['u.id' => $uploadId->toString()]],
            elementManager: $elementManager->reveal(),
            uploadService: $uploadService->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 1 expired upload(s).', $commandTester->getDisplay());
    }

    public function testCommandAppliesGracePeriodToDeletionThreshold(): void
    {
        $gracePeriodInSeconds = 1800;
        $summaryReference = null;

        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::that(function (Statement $statement) use ($gracePeriodInSeconds) {
            $parameters = $statement->getParameters();
            if (!isset($parameters['deletionThreshold']) || !$parameters['deletionThreshold'] instanceof DateTime) {
                return false;
            }
            $expectedThreshold = (new DateTime())->modify(sprintf('-%d seconds', $gracePeriodInSeconds));
            $actualThreshold = $parameters['deletionThreshold'];

            // allow a small delta to account for test execution time
            return abs($expectedThreshold->getTimestamp() - $actualThreshold->getTimestamp()) <= 2;
        }))->shouldBeCalledOnce()->willReturn(new SummarizedResult($summaryReference, []));

        $command = $this->buildCommand(
            expiredUploadCanBeDeletedAfterExpirationInSeconds: $gracePeriodInSeconds,
            client: $client->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
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

        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUploadAndChunks(Argument::any())->shouldBeCalledTimes(2);

        $command = $this->buildCommand(
            expiredUploadRows: [
                ['u.id' => $uploadId1->toString()],
                ['u.id' => $uploadId2->toString()],
            ],
            elementManager: $elementManager->reveal(),
            uploadFactory: $uploadFactory->reveal(),
            uploadService: $uploadService->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString('Deleted 2 expired upload(s).', $commandTester->getDisplay());
    }
}
