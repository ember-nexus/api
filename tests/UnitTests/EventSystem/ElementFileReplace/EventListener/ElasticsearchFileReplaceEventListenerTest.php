<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFileReplace\EventListener;

use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\EventSystem\ElementFileReplace\EventListener\ElasticsearchFileReplaceEventListener;
use App\Service\QueueService;
use App\Type\RabbitMQQueueType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;

#[Small]
#[CoversClass(ElasticsearchFileReplaceEventListener::class)]
class ElasticsearchFileReplaceEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testPublishesReindexEventWithElementId(): void
    {
        $elementId = UuidV4::uuid4();
        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            ['elementId' => $elementId->toString()]
        )->shouldBeCalledOnce();

        $listener = new ElasticsearchFileReplaceEventListener($queueService->reveal());
        $listener->onElementFileReplaceEvent(new ElementFileReplaceEvent($elementId));
    }
}
