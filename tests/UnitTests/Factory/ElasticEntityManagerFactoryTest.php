<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory;

use App\Factory\ElasticEntityManagerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Small]
#[CoversClass(ElasticEntityManagerFactory::class)]
class ElasticEntityManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testElasticEntityManagerFactory(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $elasticEntityManagerFactory = new ElasticEntityManagerFactory(
            $eventDispatcher,
            $logger,
            'some auth'
        );

        $this->assertTrue(method_exists($elasticEntityManagerFactory, 'createElasticEntityManager'));
    }
}
