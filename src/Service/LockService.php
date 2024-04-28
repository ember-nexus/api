<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\LockInterface;
use App\Factory\Exception\Client412PreconditionFailedExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use Predis\Client as RedisClient;
use Predis\Response\Status;

class LockService
{
    public function __construct(
        private RedisClient $redisClient,
        private Client412PreconditionFailedExceptionFactory $client412PreconditionFailedExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    public function acquireLock(LockInterface $lock): LockInterface
    {
        $status = $this->redisClient->set($lock->getLockKey(), $lock->getLockValue(), 'EX', $lock->getLockTimeoutInSeconds(), 'NX');

        if ($status instanceof Status) {
            if ('OK' === $status->getPayload()) {
                $lock->setLocked(true);

                return $lock;
            }
        }

        throw $this->client412PreconditionFailedExceptionFactory->createFromTemplate('Unable to acquire lock. Please retry your request at a later time.');
    }

    public function releaseLock(LockInterface $lock): LockInterface
    {
        if (!$lock->isLocked()) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Releasing lock of unlocked lock is not possible.');
        }
        $this->redisClient->del($lock->getLockKey());

        $lock->setLocked(false);

        return $lock;
    }
}
