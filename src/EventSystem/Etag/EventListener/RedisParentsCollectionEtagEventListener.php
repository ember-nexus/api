<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\Factory\Type\RedisKeyTypeFactory;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class RedisParentsCollectionEtagEventListener
{
    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyTypeFactory $redisKeyTypeFactory,
        private LoggerInterface $logger
    ) {
    }

    public function onParentsCollectionEtagEvent(ParentsCollectionEtagEvent $event): void
    {
        $redisKey = $this->redisKeyTypeFactory->getEtagParentsCollectionRedisKey($event->getChildUuid());
        $this->logger->debug(
            'Trying to find Etag for parents collection in Redis.',
            [
                'childUuid' => $event->getChildUuid()->toString(),
                'redisKey' => $redisKey,
            ]
        );
        $etag = $this->redisClient->get($redisKey);
        if (null === $etag) {
            $this->logger->debug(
                'Unable to find Etag for parents collection in Redis.',
                [
                    'childUuid' => $event->getChildUuid()->toString(),
                    'redisKey' => $redisKey,
                ]
            );

            return;
        }
        if ($etag === RedisValueType::NULL->value) {
            $etag = null;
        }
        $this->logger->debug(
            'Found Etag for parents collection in Redis.',
            [
                'childUuid' => $event->getChildUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );
        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
