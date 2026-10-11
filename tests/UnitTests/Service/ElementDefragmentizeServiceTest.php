<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

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

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(Argument::type(NodeElementDefragmentizeEvent::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($cypherFragment, $documentFragment) {
                $event = $args[0];
                /** @var NodeElementDefragmentizeEvent $event */
                TestCase::assertSame($cypherFragment, $event->getCypherFragment());
                TestCase::assertSame($documentFragment, $event->getDocumentFragment());

                $event->getNodeElement()->addProperty('key', 'value');

                return $event;
            });

        $elementFragmentizeService = new ElementDefragmentizeService($eventDispatcher->reveal());

        $nodeElement = $elementFragmentizeService->defragmentize($cypherFragment, $documentFragment);
        $this->assertSame('value', $nodeElement->getProperty('key'));
    }

    public function testDefragmentizeRelation(): void
    {
        $cypherFragment = new CypherRelation();
        $documentFragment = new MongoDocument();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(Argument::type(RelationElementDefragmentizeEvent::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($cypherFragment, $documentFragment) {
                $event = $args[0];
                /** @var RelationElementDefragmentizeEvent $event */
                TestCase::assertSame($cypherFragment, $event->getCypherFragment());
                TestCase::assertSame($documentFragment, $event->getDocumentFragment());

                $event->getRelationElement()->addProperty('key', 'value');

                return $event;
            });

        $elementFragmentizeService = new ElementDefragmentizeService($eventDispatcher->reveal());

        $relationElement = $elementFragmentizeService->defragmentize($cypherFragment, $documentFragment);
        $this->assertSame('value', $relationElement->getProperty('key'));
    }
}
