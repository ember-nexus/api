<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventListener;

use App\Event\ElementToRawEvent;
use App\EventListener\ElementToRawEventListener;
use App\Type\RelationElement;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\UuidV4;

class RelationElementFragmentizeEventListenerTest extends TestCase
{
    public function testRelationElementToRaw(): void
    {
        $element = (new RelationElement())
            ->setType('RELATION')
            ->setIdentifier(UuidV4::fromString('78f61245-8cfd-4039-ac7e-61407fa7e969'))
            ->setStartNode(UuidV4::fromString('2f84b61f-e062-4006-8915-45c951d58b68'))
            ->setEndNode(UuidV4::fromString('74f354c2-8367-4e61-98da-2a032137af34'))
            ->addProperty('someProperty', 'some value');

        $event = new ElementToRawEvent($element);
        $listener = new ElementToRawEventListener();
        $listener->onElementToRawEvent($event);
        $rawData = $event->getRawData();

        $this->assertIsArray($rawData);
        $this->assertSame('78f61245-8cfd-4039-ac7e-61407fa7e969', $rawData['id']);
        $this->assertSame('RELATION', $rawData['type']);
        $this->assertSame('2f84b61f-e062-4006-8915-45c951d58b68', $rawData['start']);
        $this->assertSame('74f354c2-8367-4e61-98da-2a032137af34', $rawData['end']);
        $this->assertSame('some value', $rawData['data']['someProperty']);
        $this->assertSame('type', array_keys($rawData)[0]);
        $this->assertSame('id', array_keys($rawData)[1]);
        $this->assertSame('start', array_keys($rawData)[2]);
        $this->assertSame('end', array_keys($rawData)[3]);
        $this->assertSame('data', array_keys($rawData)[4]);
    }
}
