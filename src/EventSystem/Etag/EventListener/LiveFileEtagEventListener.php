<?php

declare(strict_types=1);

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\FileEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class LiveFileEtagEventListener
{
    public const int REDIS_FILE_TTL_IN_SECONDS = 3600;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private EtagCalculatorService $etagCalculatorService,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onFileEtagEvent(FileEtagEvent $event): void
    {
        $etag = $this->etagCalculatorService->calculateFileEtag($event->getElementId());
        $redisKey = $this->redisKeyTypeFactory->getEtagFileRedisKey($event->getElementId());

        $this->logger->debug(
            'Trying to persist Etag for file in Redis.',
            [
                'elementId' => $event->getElementId()->toString(),
                'redisKey' => (string) $redisKey,
                'etag' => $etag,
            ]
        );

        if ($etag) {
            $redisValue = $etag;
            $this->redisClient->set((string) $redisKey, $redisValue, 'EX', self::REDIS_FILE_TTL_IN_SECONDS);
        }

        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
