<?php

declare(strict_types=1);

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class LiveElementEtagEventListener
{
    public const int REDIS_ELEMENT_TTL_IN_SECONDS = 3600;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private EtagCalculatorService $etagCalculatorService,
        private LoggerInterface $logger
    ) {
    }

    public function onElementEtagEvent(ElementEtagEvent $event): void
    {
        $etag = $this->etagCalculatorService->calculateElementEtag($event->getElementUuid());
        $redisKey = $this->redisKeyTypeFactory->getEtagElementRedisKey($event->getElementUuid());

        $this->logger->debug(
            'Trying to persist Etag for element in Redis.',
            [
                'elementUuid' => $event->getElementUuid()->toString(),
                'redisKey' => (string) $redisKey,
                'etag' => $etag,
            ]
        );

        if ($etag) {
            $redisValue = $etag;
            $this->redisClient->set((string) $redisKey, $redisValue, 'EX', self::REDIS_ELEMENT_TTL_IN_SECONDS);
        }

        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
