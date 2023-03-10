<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventListener;

use App\Event\ElementToRawEvent;
use App\EventListener\ElementToRawEventListener;
use App\Type\NodeElement;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\UuidV4;

class NodeElementFragmentizeEventListenerTest extends TestCase
{
    public function testNodeElementToRaw(): void
    {
        $element = (new NodeElement())
            ->setLabel('Node')
            ->setIdentifier(UuidV4::fromString('78f61245-8cfd-4039-ac7e-61407fa7e969'))
            ->addProperty('someProperty', 'some value');

        $event = new ElementToRawEvent($element);
        $listener = new ElementToRawEventListener();
        $listener->onElementToRawEvent($event);
        $rawData = $event->getRawData();

        $this->assertIsArray($rawData);
        $this->assertSame('78f61245-8cfd-4039-ac7e-61407fa7e969', $rawData['id']);
        $this->assertSame('Node', $rawData['type']);
        $this->assertSame('some value', $rawData['data']['someProperty']);
        $this->assertSame('type', array_keys($rawData)[0]);
        $this->assertSame('id', array_keys($rawData)[1]);
        $this->assertSame('data', array_keys($rawData)[2]);
    }
}
