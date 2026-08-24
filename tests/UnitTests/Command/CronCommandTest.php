<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\Cron\DeleteExpiredUploadsCommand;
use App\Command\Cron\ReindexFilesCommand;
use App\Command\CronCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Service\ElementManager;
use App\Service\QueueService;
use App\Service\S3Service;
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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(CronCommand::class)]
class CronCommandTest extends TestCase
{
    use ProphecyTrait;

    private function buildDeleteExpiredUploadsCommand(bool $isCronDisabled): DeleteExpiredUploadsCommand
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn($isCronDisabled);

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
            $bag->reveal(),
            $emberNexusConfiguration->reveal(),
            $cypherEntityManager->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->prophesize(UploadFactory::class)->reveal(),
            $this->prophesize(UploadService::class)->reveal(),
            $this->prophesize(FileOperationFactory::class)->reveal(),
            $this->prophesize(S3Service::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
    }

    private function buildReindexFilesCommand(bool $isCronDisabled): ReindexFilesCommand
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn($isCronDisabled);

        $queueService = $this->prophesize(QueueService::class);
        if (!$isCronDisabled) {
            $queueService->consumeQueue(Argument::any(), Argument::any())->willReturn(0);
        }

        return new ReindexFilesCommand(
            $bag->reveal(),
            $queueService->reveal(),
            $this->prophesize(ElementManager::class)->reveal()
        );
    }

    private function buildCommand(bool $isCronDisabled): CronCommand
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn($isCronDisabled);

        return new CronCommand(
            $bag->reveal(),
            $this->buildDeleteExpiredUploadsCommand($isCronDisabled),
            $this->buildReindexFilesCommand($isCronDisabled)
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
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn('not-a-boolean');

        $command = new CronCommand(
            $bag->reveal(),
            $this->buildDeleteExpiredUploadsCommand(true),
            $this->buildReindexFilesCommand(true)
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
        $this->assertStringContainsString('Finished.', $display);
    }
}
