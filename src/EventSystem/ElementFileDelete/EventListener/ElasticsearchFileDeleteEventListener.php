<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFileDelete\EventListener;

use App\EventSystem\ElementFileDelete\Event\ElementFileDeleteEvent;
use App\Service\QueueService;
use App\Type\RabbitMQQueueType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class ElasticsearchFileDeleteEventListener
{
    public function __construct(
        private QueueService $queueService,
    ) {
    }

    #[AsEventListener]
    public function onElementFileDeleteEvent(ElementFileDeleteEvent $event): void
    {
        $this->queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            [
                'elementId' => $event->getElementId()->toString(),
            ]
        );
    }
}
