<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFileReplace\EventListener;

use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Service\QueueService;
use App\Type\RabbitMQQueueType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class ElasticsearchFileReplaceEventListener
{
    public function __construct(
        private QueueService $queueService
    ) {
    }

    #[AsEventListener]
    public function onElementFileReplaceEvent(ElementFileReplaceEvent $event): void
    {
        $this->queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            [
                'elementId' => $event->getElementId()->toString()
            ]
        );
    }

}
