<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Factory\EventSystem\ElementFragmentize\Event;

use App\Factory\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEventFactory;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(RelationElementFragmentizeEventFactory::class)]
class RelationElementFragmentizeEventFactoryTest extends TestCase
{
    public function testCreateRelationElementFragmentizeEvent(): void
    {
        $relationElement = new RelationElement();
        $relationElementFragmentizeEventFactory = new RelationElementFragmentizeEventFactory();

        $event = $relationElementFragmentizeEventFactory->createRelationElementFragmentizeEvent(
            $relationElement
        );

        $this->assertSame($relationElement, $event->getRelationElement());
        $this->assertNotNull($event->getCypherFragment());
        $this->assertNotNull($event->getElasticFragment());
        $this->assertNotNull($event->getMongoFragment());
        $this->assertNull($event->getFileFragment());
    }
}
