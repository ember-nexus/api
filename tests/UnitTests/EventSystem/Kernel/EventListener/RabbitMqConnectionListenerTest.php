<?php

namespace App\tests\UnitTests\EventSystem\Kernel\EventListener;

use App\EventSystem\Kernel\EventListener\EtagConnectionListener;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RabbitMqConnectionListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testEvent(): void
    {
        $amqpStreamConnection = $this->prophesize(AMQPStreamConnection::class);
        $amqpStreamConnection->close()->shouldBeCalledOnce()->willReturn();
        $eventListener = new EtagConnectionListener($amqpStreamConnection->reveal());
        $eventListener->onKernelTerminate();
    }
}
