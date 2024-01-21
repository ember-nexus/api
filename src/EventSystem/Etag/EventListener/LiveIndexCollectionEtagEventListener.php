<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class LiveIndexCollectionEtagEventListener
{
    public const int REDIS_INDEX_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private EtagCalculatorService $etagCalculatorService,
        private LoggerInterface $logger
    ) {
    }

    public function onIndexCollectionEtagEvent(IndexCollectionEtagEvent $event): void
    {
        $etag = $this->etagCalculatorService->calculateIndexCollectionEtag($event->getUserUuid());
        $redisKey = $this->redisKeyTypeFactory->getEtagIndexCollectionRedisKey($event->getUserUuid());

        $this->logger->debug(
            'Trying to persist Etag for index collection in Redis.',
            [
                'userUuid' => $event->getUserUuid()->toString(),
                'redisKey' => (string) $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set($redisKey, $redisValue, 'EX', self::REDIS_INDEX_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
