<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command\Cron;

use App\Command\Cron\ReindexFilesCommand;
use App\Service\ElementManager;
use App\Service\QueueService;
use App\Type\NodeElement;
use App\Type\RabbitMQQueueType;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Small]
#[CoversClass(ReindexFilesCommand::class)]
class ReindexFilesCommandTest extends TestCase
{
    use ProphecyTrait;

    private function buildCommand(
        bool $isCronDisabled = false,
        ?QueueService $queueService = null,
        ?ElementManager $elementManager = null,
    ): ReindexFilesCommand {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn($isCronDisabled);

        return new ReindexFilesCommand(
            $bag->reveal(),
            $queueService ?? $this->prophesize(QueueService::class)->reveal(),
            $elementManager ?? $this->prophesize(ElementManager::class)->reveal()
        );
    }

    public function testCommandStopsEarlyIfCronIsDisabled(): void
    {
        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::cetera())->shouldNotBeCalled();

        $command = $this->buildCommand(isCronDisabled: true, queueService: $queueService->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Cron is disabled', $commandTester->getDisplay());
    }

    public function testCommandThrowsIfCronDisabledParameterIsNotBoolean(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn('not-a-boolean');

        $command = new ReindexFilesCommand(
            $bag->reveal(),
            $this->prophesize(QueueService::class)->reveal(),
            $this->prophesize(ElementManager::class)->reveal()
        );

        $this->expectException(LogicException::class);
        (new CommandTester($command))->execute([]);
    }

    public function testCommandDoesNothingIfQueueIsEmpty(): void
    {
        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE), Argument::type('callable'))
            ->shouldBeCalledOnce()
            ->willReturn(0);

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->shouldNotBeCalled();

        $command = $this->buildCommand(queueService: $queueService->reveal(), elementManager: $elementManager->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Reindexed 0 element file(s).', $commandTester->getDisplay());
    }

    public function testCommandResyncsElementForEachQueuedMessage(): void
    {
        $elementId = Uuid::fromString('4f4c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d4');
        $element = (new NodeElement())->setId($elementId)->setLabel('Data');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $elementId->toString()))
            ->shouldBeCalledOnce()
            ->willReturn($element);
        $elementManager->merge(Argument::is($element))->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE), Argument::type('callable'))
            ->will(function ($args) use ($elementId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler(['elementId' => $elementId->toString()]);

                return 1;
            });

        $command = $this->buildCommand(queueService: $queueService->reveal(), elementManager: $elementManager->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Reindexed 1 element file(s).', $commandTester->getDisplay());
    }

    public function testCommandSkipsMessageIfElementWasAlreadyDeleted(): void
    {
        $elementId = Uuid::fromString('5a5c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d5');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->willReturn(null);
        $elementManager->merge(Argument::any())->shouldNotBeCalled();

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE), Argument::type('callable'))
            ->will(function ($args) use ($elementId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler(['elementId' => $elementId->toString()]);

                return 1;
            });

        $command = $this->buildCommand(queueService: $queueService->reveal(), elementManager: $elementManager->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
