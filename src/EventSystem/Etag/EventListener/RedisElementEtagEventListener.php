<?php

declare(strict_types=1);

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Type\Etag;
use App\Type\RedisValueType;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class RedisElementEtagEventListener
{
    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function onElementEtagEvent(ElementEtagEvent $event): void
    {
        $redisKey = $this->redisKeyTypeFactory->getEtagElementRedisKey($event->getElementId());
        $this->logger->debug(
            'Trying to find Etag for element in Redis.',
            [
                'elementId' => $event->getElementId()->toString(),
                'redisKey' => (string) $redisKey,
            ]
        );
        $rawEtag = $this->redisClient->get((string) $redisKey);
        if (null === $rawEtag) {
            $this->logger->debug(
                'Unable to find Etag for element in Redis.',
                [
                    'elementId' => $event->getElementId()->toString(),
                    'redisKey' => (string) $redisKey,
                ]
            );

            return;
        }
        $etag = new Etag($rawEtag);
        if ((string) $etag === RedisValueType::NULL->value) {
            $etag = null;
        }
        $this->logger->debug(
            'Found Etag for element in Redis.',
            [
                'elementId' => $event->getElementId()->toString(),
                'redisKey' => (string) $redisKey,
                'etag' => $etag,
            ]
        );
        $event->setEtag($etag);
        $event->stopPropagation();
    }
}
