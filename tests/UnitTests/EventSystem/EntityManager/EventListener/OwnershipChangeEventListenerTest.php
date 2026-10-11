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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

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
            ->setId(Uuid::fromString('11111111-1111-1111-1111-111111111111'))
            ->setType($type)
            ->setStart(Uuid::fromString('22222222-2222-2222-2222-222222222222'))
            ->setEnd(Uuid::fromString('33333333-3333-3333-3333-333333333333'));
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
                'relationId' => '11111111-1111-1111-1111-111111111111',
                'relationType' => $type,
                'startId' => '22222222-2222-2222-2222-222222222222',
                'endId' => '33333333-3333-3333-3333-333333333333',
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
                'relationId' => '11111111-1111-1111-1111-111111111111',
                'relationType' => 'OWNS',
                'startId' => '22222222-2222-2222-2222-222222222222',
                'endId' => '33333333-3333-3333-3333-333333333333',
                'eventKind' => 'delete',
                'tries' => 0,
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostDelete(new ElementPostDeleteEvent($relation));
    }

    public function testNodeElementIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('44444444-4444-4444-4444-444444444444'))->setLabel('Data');

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($queueService->reveal());
        $listener->onElementPostCreate(new ElementPostCreateEvent($node));
    }

    public function testRelationWithoutTypeIsIgnored(): void
    {
        $relation = (new RelationElement())->setId(Uuid::fromString('11111111-1111-1111-1111-111111111111'));

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
