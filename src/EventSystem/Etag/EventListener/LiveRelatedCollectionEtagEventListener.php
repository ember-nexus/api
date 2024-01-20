<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class LiveRelatedCollectionEtagEventListener
{
    public const int REDIS_RELATED_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private EtagCalculatorService $etagCalculatorService,
        private LoggerInterface $logger
    ) {
    }

    public function onRelatedCollectionEtagEvent(RelatedCollectionEtagEvent $event): void
    {
        $this->logger->debug(
            'Calculating Etag for related collection.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
            ]
        );
        $etag = $this->etagCalculatorService->calculateRelatedCollectionEtag($event->getCenterUuid());
        $this->logger->debug(
            'Calculated Etag for related collection.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
                'etag' => $etag,
            ]
        );

        $redisKey = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey($event->getCenterUuid());

        $this->logger->debug(
            'Trying to persist Etag for related collection in Redis.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set($redisKey, $redisValue, 'EX', self::REDIS_RELATED_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
