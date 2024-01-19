<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class RedisIndexCollectionEtagEventListener
{
    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private LoggerInterface $logger
    ) {
    }

    public function onIndexCollectionEtagEvent(IndexCollectionEtagEvent $event): void
    {
        $redisKey = $this->redisKeyTypeFactory->getEtagIndexCollectionRedisKey($event->getUserUuid());
        $this->logger->debug(
            'Trying to find Etag for index collection in Redis.',
            [
                'userUuid' => $event->getUserUuid()->toString(),
                'redisKey' => $redisKey,
            ]
        );
        $etag = $this->redisClient->get($redisKey);
        if (null === $etag) {
            $this->logger->debug(
                'Unable to find Etag for index collection in Redis.',
                [
                    'userUuid' => $event->getUserUuid()->toString(),
                    'redisKey' => $redisKey,
                ]
            );

            return;
        }
        if ($etag === RedisValueType::NULL->value) {
            $etag = null;
        }
        $this->logger->debug(
            'Found Etag for index collection in Redis.',
            [
                'userUuid' => $event->getUserUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );
        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
