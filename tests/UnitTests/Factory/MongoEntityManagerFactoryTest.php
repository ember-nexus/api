<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory;

use App\Factory\MongoEntityManagerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Small]
#[CoversClass(MongoEntityManagerFactory::class)]
class MongoEntityManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testMongoEntityManagerFactory(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $mongoEntityManagerFactory = new MongoEntityManagerFactory(
            $eventDispatcher,
            $logger,
            'some auth'
        );

        $this->assertTrue(method_exists($mongoEntityManagerFactory, 'createMongoEntityManager'));
    }
}
