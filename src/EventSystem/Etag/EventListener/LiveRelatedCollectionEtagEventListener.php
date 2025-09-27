<?php

declare(strict_types=1);

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class LiveRelatedCollectionEtagEventListener
{
    public const int REDIS_RELATED_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private EtagCalculatorService $etagCalculatorService,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onRelatedCollectionEtagEvent(RelatedCollectionEtagEvent $event): void
    {
        $etag = $this->etagCalculatorService->calculateRelatedCollectionEtag($event->getCenterId());
        $redisKey = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey($event->getCenterId());

        $this->logger->debug(
            'Trying to persist Etag for related collection in Redis.',
            [
                'centerId' => $event->getCenterId()->toString(),
                'redisKey' => (string) $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set((string) $redisKey, (string) $redisValue, 'EX', self::REDIS_RELATED_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
