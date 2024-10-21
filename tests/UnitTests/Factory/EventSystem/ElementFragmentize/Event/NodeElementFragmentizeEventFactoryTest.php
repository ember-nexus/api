<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\EventSystem\ElementFragmentize\Event;

use App\Factory\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEventFactory;
use App\Type\NodeElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(NodeElementFragmentizeEventFactory::class)]
class NodeElementFragmentizeEventFactoryTest extends TestCase
{
    public function testCreateNodeElementFragmentizeEvent(): void
    {
        $nodeElement = new NodeElement();
        $nodeElementFragmentizeEventFactory = new NodeElementFragmentizeEventFactory();

        $event = $nodeElementFragmentizeEventFactory->createNodeElementFragmentizeEvent(
            $nodeElement
        );

        $this->assertSame($nodeElement, $event->getNodeElement());
        $this->assertNotNull($event->getCypherFragment());
        $this->assertNotNull($event->getElasticFragment());
        $this->assertNotNull($event->getMongoFragment());
        $this->assertNull($event->getFileFragment());
    }
}
