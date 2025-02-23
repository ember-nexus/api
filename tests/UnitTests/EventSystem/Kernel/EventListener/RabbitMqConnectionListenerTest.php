<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Kernel\EventListener;

use App\EventSystem\Kernel\EventListener\RabbitMqConnectionListener;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(RabbitMqConnectionListener::class)]
class RabbitMqConnectionListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testEvent(): void
    {
        $amqpStreamConnection = $this->prophesize(AMQPStreamConnection::class);
        $amqpStreamConnection->close()->shouldBeCalledOnce()->willReturn();
        $eventListener = new RabbitMqConnectionListener($amqpStreamConnection->reveal());
        $eventListener->onKernelTerminate();
    }
}
