<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\Cron\DeleteExpiredUploadsCommand;
use App\Command\Cron\ReindexFilesCommand;
use App\Command\Cron\UpdateOwnershipCommand;
use App\Command\CronCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Service\CronExecutionGateService;
use App\Service\DeletionService;
use App\Service\ElementManager;
use App\Service\ExpiredUploadDeletionAttemptService;
use App\Service\QueueService;
use App\Service\SearchAccessCalculatorService;
use App\Service\UploadService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

#[Small]
#[CoversClass(CronCommand::class)]
class CronCommandTest extends TestCase
{
    use ProphecyTrait;

    private function buildCronExecutionGateService(bool $isCronDisabled): CronExecutionGateService
    {
        $cronExecutionGateService = $this->prophesize(CronExecutionGateService::class);
        $cronExecutionGateService->shouldSkipExecution()->willReturn($isCronDisabled);

        return $cronExecutionGateService->reveal();
    }

    private function buildDeleteExpiredUploadsCommand(bool $isCronDisabled): DeleteExpiredUploadsCommand
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileExpiredUploadCanBeDeletedAfterExpirationInSeconds()->willReturn(3600);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        if (!$isCronDisabled) {
            $summaryReference = null;
            $client = $this->prophesize(ClientInterface::class);
            $client->runStatement(Argument::any())->willReturn(new SummarizedResult($summaryReference, []));
            $cypherEntityManager->getClient()->willReturn($client->reveal());
        }

        return new DeleteExpiredUploadsCommand(
            $this->buildCronExecutionGateService($isCronDisabled),
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->prophesize(UploadFactory::class)->reveal(),
            $this->prophesize(UploadService::class)->reveal(),
            $this->prophesize(DeletionService::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
            $this->prophesize(ExpiredUploadDeletionAttemptService::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );
    }

    private function buildReindexFilesCommand(bool $isCronDisabled): ReindexFilesCommand
    {
        $queueService = $this->prophesize(QueueService::class);
        if (!$isCronDisabled) {
            $queueService->consumeQueue(Argument::any(), Argument::any())->willReturn(0);
        }

        return new ReindexFilesCommand(
            $this->buildCronExecutionGateService($isCronDisabled),
            $queueService->reveal(),
            $this->prophesize(ElementManager::class)->reveal()
        );
    }

    private function buildUpdateOwnershipCommand(bool $isCronDisabled): UpdateOwnershipCommand
    {
        $queueService = $this->prophesize(QueueService::class);
        if (!$isCronDisabled) {
            $queueService->consumeQueue(Argument::any(), Argument::any())->willReturn(0);
        }

        return new UpdateOwnershipCommand(
            $this->buildCronExecutionGateService($isCronDisabled),
            $queueService->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->prophesize(SearchAccessCalculatorService::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->prophesize(CypherEntityManager::class)->reveal(),
        );
    }

    private function buildCommand(bool $isCronDisabled): CronCommand
    {
        return new CronCommand(
            $this->buildCronExecutionGateService($isCronDisabled),
            $this->buildDeleteExpiredUploadsCommand($isCronDisabled),
            $this->buildReindexFilesCommand($isCronDisabled),
            $this->buildUpdateOwnershipCommand($isCronDisabled),
            $this->prophesize(LoggerInterface::class)->reveal()
        );
    }

    public function testCommandStopsEarlyIfCronIsDisabled(): void
    {
        $command = $this->buildCommand(isCronDisabled: true);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Cron is disabled', $commandTester->getDisplay());
    }

    public function testCommandThrowsIfCronDisabledParameterIsNotBoolean(): void
    {
        $cronExecutionGateService = $this->prophesize(CronExecutionGateService::class);
        $cronExecutionGateService->shouldSkipExecution()->willThrow(
            new LogicException('Expected "isCronDisabled" to be of type boolean, got string.')
        );

        $command = new CronCommand(
            $cronExecutionGateService->reveal(),
            $this->buildDeleteExpiredUploadsCommand(true),
            $this->buildReindexFilesCommand(true),
            $this->buildUpdateOwnershipCommand(true),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(LogicException::class);
        (new CommandTester($command))->execute([]);
    }

    public function testCommandDispatchesBothSubCommandsWhenEnabled(): void
    {
        $command = $this->buildCommand(isCronDisabled: false);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('No expired uploads found.', $display);
        $this->assertStringContainsString('Reindexed 0 element file(s).', $display);
        $this->assertStringContainsString('Processed 0 queue message(s), updated search access of 0 element(s).', $display);
        $this->assertStringContainsString('Finished.', $display);
    }

    public function testFailingSubCommandDoesNotPreventFollowingOnesAndFailsCron(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileExpiredUploadCanBeDeletedAfterExpirationInSeconds()->willReturn(3600);
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willThrow(new RuntimeException('neo4j down'));
        $deleteExpiredUploadsCommand = new DeleteExpiredUploadsCommand(
            $this->buildCronExecutionGateService(false),
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->prophesize(UploadFactory::class)->reveal(),
            $this->prophesize(UploadService::class)->reveal(),
            $this->prophesize(DeletionService::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
            $this->prophesize(ExpiredUploadDeletionAttemptService::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::containingString('neo4j down'), Argument::any())->shouldBeCalledOnce();

        $command = new CronCommand(
            $this->buildCronExecutionGateService(false),
            $deleteExpiredUploadsCommand,
            $this->buildReindexFilesCommand(false),
            $this->buildUpdateOwnershipCommand(false),
            $logger->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Reindexed 0 element file(s).', $commandTester->getDisplay());
    }
}
