<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class RedisRelatedCollectionEtagEventListener
{
    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private LoggerInterface $logger
    ) {
    }

    public function onRelatedCollectionEtagEvent(RelatedCollectionEtagEvent $event): void
    {
        $redisKey = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey($event->getCenterUuid());
        $this->logger->debug(
            'Trying to find Etag for related collection in Redis.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
                'redisKey' => $redisKey,
            ]
        );
        $etag = $this->redisClient->get($redisKey);
        if (null === $etag) {
            $this->logger->debug(
                'Unable to find Etag for related collection in Redis.',
                [
                    'centerUuid' => $event->getCenterUuid()->toString(),
                    'redisKey' => $redisKey,
                ]
            );

            return;
        }
        if ($etag === RedisValueType::NULL->value) {
            $etag = null;
        }
        $this->logger->debug(
            'Found Etag for related collection in Redis.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );
        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
