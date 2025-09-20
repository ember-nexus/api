<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Service\ElementDefragmentizeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherDataStructures\Type\Node as CypherNode;
use Syndesi\CypherDataStructures\Type\Relation as CypherRelation;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

#[Small]
#[CoversClass(ElementDefragmentizeService::class)]
class ElementDefragmentizeServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testDefragmentizeNode(): void
    {
        $cypherFragment = new CypherNode();
        $documentFragment = new MongoDocument();
        $fileFragment = null;

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(Argument::type(NodeElementDefragmentizeEvent::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($cypherFragment, $documentFragment, $fileFragment) {
                $event = $args[0];
                /** @var NodeElementDefragmentizeEvent $event */
                TestCase::assertSame($cypherFragment, $event->getCypherFragment());
                TestCase::assertSame($documentFragment, $event->getDocumentFragment());
                TestCase::assertSame($fileFragment, $event->getFileFragment());

                $event->getNodeElement()->addProperty('key', 'value');

                return $event;
            });

        $elementFragmentizeService = new ElementDefragmentizeService($eventDispatcher->reveal());

        $nodeElement = $elementFragmentizeService->defragmentize($cypherFragment, $documentFragment, $fileFragment);
        $this->assertSame('value', $nodeElement->getProperty('key'));
    }

    public function testDefragmentizeRelation(): void
    {
        $cypherFragment = new CypherRelation();
        $documentFragment = new MongoDocument();
        $fileFragment = null;

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(Argument::type(RelationElementDefragmentizeEvent::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($cypherFragment, $documentFragment, $fileFragment) {
                $event = $args[0];
                /** @var RelationElementDefragmentizeEvent $event */
                TestCase::assertSame($cypherFragment, $event->getCypherFragment());
                TestCase::assertSame($documentFragment, $event->getDocumentFragment());
                TestCase::assertSame($fileFragment, $event->getFileFragment());

                $event->getRelationElement()->addProperty('key', 'value');

                return $event;
            });

        $elementFragmentizeService = new ElementDefragmentizeService($eventDispatcher->reveal());

        $relationElement = $elementFragmentizeService->defragmentize($cypherFragment, $documentFragment, $fileFragment);
        $this->assertSame('value', $relationElement->getProperty('key'));
    }
}
