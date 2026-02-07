<?php

declare(strict_types=1);

namespace App\Type;

enum RabbitMQQueueType: string
{
    case ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE = 'ELASTICSEARCH_UPDATE_OWNERSHIP';
    case ELASTICSEARCH_REINDEX_FILE_QUEUE = 'ELASTICSEARCH_REINDEX_FILE';
}
