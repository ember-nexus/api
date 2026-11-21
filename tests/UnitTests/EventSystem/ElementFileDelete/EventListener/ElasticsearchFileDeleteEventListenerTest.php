<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFileDelete\EventListener;

use App\EventSystem\ElementFileDelete\Event\ElementFileDeleteEvent;
use App\EventSystem\ElementFileDelete\EventListener\ElasticsearchFileDeleteEventListener;
use App\Service\QueueService;
use App\Type\RabbitMQQueueType;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(ElasticsearchFileDeleteEventListener::class)]
class ElasticsearchFileDeleteEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testPublishesReindexEventWithElementId(): void
    {
        $elementId = Uuid::generate();
        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            ['elementId' => $elementId->toString()]
        )->shouldBeCalledOnce();

        $listener = new ElasticsearchFileDeleteEventListener($queueService->reveal());
        $listener->onElementFileDeleteEvent(new ElementFileDeleteEvent($elementId));
    }
}
