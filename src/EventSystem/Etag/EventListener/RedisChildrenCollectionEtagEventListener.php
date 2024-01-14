<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\Helper\RedisKeyHelper;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class RedisChildrenCollectionEtagEventListener
{
    public function __construct(
        private RedisClient $redisClient,
        private LoggerInterface $logger
    ) {
    }

    public function onChildrenCollectionEtagEvent(ChildrenCollectionEtagEvent $event): void
    {
        $redisKey = RedisKeyHelper::getEtagChildrenCollectionRedisKey($event->getParentUuid());
        $this->logger->debug(
            'Trying to find Etag for children collection in Redis.',
            [
                'parentUuid' => $event->getParentUuid()->toString(),
                'redisKey' => $redisKey,
            ]
        );
        $etag = $this->redisClient->get($redisKey);
        if (null === $etag) {
            $this->logger->debug(
                'Unable to find Etag for children collection in Redis.',
                [
                    'parentUuid' => $event->getParentUuid()->toString(),
                    'redisKey' => $redisKey,
                ]
            );

            return;
        }
        if ($etag === RedisValueType::NULL->value) {
            $etag = null;
        }
        $this->logger->debug(
            'Found Etag for children collection in Redis.',
            [
                'parentUuid' => $event->getParentUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );
        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
