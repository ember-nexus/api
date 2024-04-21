<?php

declare(strict_types=1);

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class LiveParentsCollectionEtagEventListener
{
    public const int REDIS_PARENTS_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private EtagCalculatorService $etagCalculatorService,
        private LoggerInterface $logger
    ) {
    }

    public function onParentsCollectionEtagEvent(ParentsCollectionEtagEvent $event): void
    {
        $etag = $this->etagCalculatorService->calculateParentsCollectionEtag($event->getChildUuid());
        $redisKey = $this->redisKeyTypeFactory->getEtagParentsCollectionRedisKey($event->getChildUuid());

        $this->logger->debug(
            'Trying to persist Etag for parents collection in Redis.',
            [
                'childUuid' => $event->getChildUuid()->toString(),
                'redisKey' => (string) $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set((string) $redisKey, $redisValue, 'EX', self::REDIS_PARENTS_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
