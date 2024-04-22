<?php

declare(strict_types=1);

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class LiveChildrenCollectionEtagEventListener
{
    public const int REDIS_CHILDREN_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private EtagCalculatorService $etagCalculatorService,
        private LoggerInterface $logger
    ) {
    }

    public function onChildrenCollectionEtagEvent(ChildrenCollectionEtagEvent $event): void
    {
        $etag = $this->etagCalculatorService->calculateChildrenCollectionEtag($event->getParentId());
        $redisKey = $this->redisKeyTypeFactory->getEtagChildrenCollectionRedisKey($event->getParentId());

        $this->logger->debug(
            'Trying to persist Etag for children collection in Redis.',
            [
                'parentId' => $event->getParentId()->toString(),
                'redisKey' => (string) $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set((string) $redisKey, $redisValue, 'EX', self::REDIS_CHILDREN_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
