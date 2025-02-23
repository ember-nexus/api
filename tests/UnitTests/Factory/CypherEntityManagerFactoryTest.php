<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory;

use App\Factory\CypherEntityManagerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Small]
#[CoversClass(CypherEntityManagerFactory::class)]
class CypherEntityManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCypherEntityManagerFactory(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $cypherEntityManagerFactory = new CypherEntityManagerFactory(
            $eventDispatcher,
            $logger,
            'some auth'
        );

        $this->assertTrue(method_exists($cypherEntityManagerFactory, 'createCypherEntityManager'));
    }
}
