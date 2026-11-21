<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\EventListener\OwnershipChangeEventListener;
use App\Service\AppStateService;
use App\Service\QueueService;
use App\Type\AppStateType;
use App\Type\NodeElement;
use App\Type\RabbitMQQueueType;
use App\Type\RelationElement;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(OwnershipChangeEventListener::class)]
class OwnershipChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private function buildListener(?QueueService $queueService = null, AppStateType $appState = AppStateType::DEFAULT): OwnershipChangeEventListener
    {
        $appStateService = $this->prophesize(AppStateService::class);
        $appStateService->getAppState()->willReturn($appState);

        return new OwnershipChangeEventListener(
            $queueService ?? $this->prophesize(QueueService::class)->reveal(),
            $appStateService->reveal()
        );
    }

    private function buildRelation(string $type): RelationElement
    {
        return (new RelationElement())
            ->setId(Uuid::fromString('02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf'))
            ->setType($type)
            ->setStart(Uuid::fromString('02IiNr0G8viOVKjboyDliyOxJlXj6Bii5l1U2'))
            ->setEnd(Uuid::fromString('02IiNr0G8viOVKjk87c7OoU78ONujU6JKLydP'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function triggeringTypesProvider(): array
    {
        return [
            'OWNS' => ['OWNS'],
            'HAS_SEARCH_ACCESS' => ['HAS_SEARCH_ACCESS'],
            'IS_IN_GROUP' => ['IS_IN_GROUP'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonTriggeringTypesProvider(): array
    {
        return [
            'CREATED' => ['CREATED'],
            'HAS_READ_ACCESS' => ['HAS_READ_ACCESS'],
            'HAS_CREATE_ACCESS' => ['HAS_CREATE_ACCESS'],
            'HAS_UPDATE_ACCESS' => ['HAS_UPDATE_ACCESS'],
            'HAS_DELETE_ACCESS' => ['HAS_DELETE_ACCESS'],
            'SOME_OTHER_TYPE' => ['SOME_OTHER_TYPE'],
        ];
    }

    #[DataProvider('triggeringTypesProvider')]
    public function testPostCreateOfTriggeringRelationTypePublishesEvent(string $type): void
    {
        $relation = $this->buildRelation($type);

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            [
                'relationId' => '02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf',
                'relationType' => $type,
                'startId' => '02IiNr0G8viOVKjboyDliyOxJlXj6Bii5l1U2',
                'endId' => '02IiNr0G8viOVKjk87c7OoU78ONujU6JKLydP',
                'eventKind' => 'create',
                'tries' => 0,
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostCreate(new ElementPostCreateEvent($relation));
    }

    #[DataProvider('nonTriggeringTypesProvider')]
    public function testPostCreateOfNonTriggeringRelationTypeDoesNotPublish(string $type): void
    {
        $relation = $this->buildRelation($type);

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostCreate(new ElementPostCreateEvent($relation));
    }

    public function testPostMergePublishesWithMergeEventKind(): void
    {
        $relation = $this->buildRelation('OWNS');

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            Argument::that(fn ($payload) => 'merge' === $payload['eventKind'])
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($relation));
    }

    public function testPostDeletePublishesWithDeleteEventKindAndStillCarriesFullRelationData(): void
    {
        // the relation is already gone from the graph by the time this fires, so its data must travel in the
        // payload itself
        $relation = $this->buildRelation('OWNS');

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            [
                'relationId' => '02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf',
                'relationType' => 'OWNS',
                'startId' => '02IiNr0G8viOVKjboyDliyOxJlXj6Bii5l1U2',
                'endId' => '02IiNr0G8viOVKjk87c7OoU78ONujU6JKLydP',
                'eventKind' => 'delete',
                'tries' => 0,
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostDelete(new ElementPostDeleteEvent($relation));
    }

    public function testNodeElementIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('02IiNr0G8viOVKjsRH0T4eZGx1E6MmTuYwvmm'))->setLabel('Data');

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostCreate(new ElementPostCreateEvent($node));
    }

    public function testRelationWithoutTypeIsIgnored(): void
    {
        $relation = (new RelationElement())->setId(Uuid::fromString('02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf'));

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostCreate(new ElementPostCreateEvent($relation));
    }

    public function testRelationWithoutIdIsIgnored(): void
    {
        $relation = (new RelationElement())->setType('OWNS');

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostCreate(new ElementPostCreateEvent($relation));
    }

    public function testEventDuringBackupLoadIsIgnored(): void
    {
        $relation = $this->buildRelation('OWNS');

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($queueService->reveal(), AppStateType::LOADING_BACKUP);
        $listener->onElementPostCreate(new ElementPostCreateEvent($relation));
        $listener->onElementPostMerge(new ElementPostMergeEvent($relation));
        $listener->onElementPostDelete(new ElementPostDeleteEvent($relation));
    }
}
