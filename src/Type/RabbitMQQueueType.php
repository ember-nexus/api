<?php

declare(strict_types=1);

namespace App\Type;

enum RabbitMQQueueType: string
{
    case REBUILD_SEARCH_DOCUMENT_QUEUE = 'REBUILD_SEARCH_DOCUMENT';
}
