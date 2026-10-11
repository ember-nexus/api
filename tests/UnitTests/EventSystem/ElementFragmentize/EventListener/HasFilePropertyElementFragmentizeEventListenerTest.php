<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFragmentize\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\EventListener\HasFilePropertyElementFragmentizeEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface as ElasticDocumentInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface as MongoDocumentInterface;

#[Small]
#[CoversClass(HasFilePropertyElementFragmentizeEventListener::class)]
class HasFilePropertyElementFragmentizeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testNodeEventCopiesHasFilePropertyToAllFragments(): void
    {
        $nodeElement = $this->prophesize(NodeElementInterface::class);
        $nodeElement->hasProperty('hasFile')->willReturn(true);
        $nodeElement->getProperty('hasFile')->willReturn(true);

        $cypherFragment = $this->prophesize(NodeInterface::class);
        $cypherFragment->addProperty('hasFile', true)->shouldBeCalledOnce();
        $mongoFragment = $this->prophesize(MongoDocumentInterface::class);
        $mongoFragment->addProperty('hasFile', true)->shouldBeCalledOnce();
        $elasticFragment = $this->prophesize(ElasticDocumentInterface::class);
        $elasticFragment->addProperty('hasFile', true)->shouldBeCalledOnce();

        $event = new NodeElementFragmentizeEvent(
            $nodeElement->reveal(),
            $cypherFragment->reveal(),
            $mongoFragment->reveal(),
            $elasticFragment->reveal(),
        );

        (new HasFilePropertyElementFragmentizeEventListener())->onNodeElementFragmentizeEvent($event);
    }

    public function testNodeEventDoesNothingWhenPropertyIsMissing(): void
    {
        $nodeElement = $this->prophesize(NodeElementInterface::class);
        $nodeElement->hasProperty('hasFile')->willReturn(false);

        $cypherFragment = $this->prophesize(NodeInterface::class);
        $cypherFragment->addProperty('hasFile', null)->shouldNotBeCalled();
        $mongoFragment = $this->prophesize(MongoDocumentInterface::class);
        $mongoFragment->addProperty('hasFile', null)->shouldNotBeCalled();
        $elasticFragment = $this->prophesize(ElasticDocumentInterface::class);
        $elasticFragment->addProperty('hasFile', null)->shouldNotBeCalled();

        $event = new NodeElementFragmentizeEvent(
            $nodeElement->reveal(),
            $cypherFragment->reveal(),
            $mongoFragment->reveal(),
            $elasticFragment->reveal(),
        );

        (new HasFilePropertyElementFragmentizeEventListener())->onNodeElementFragmentizeEvent($event);
        $this->addToAssertionCount(1);
    }

    public function testRelationEventCopiesHasFilePropertyToAllFragments(): void
    {
        $relationElement = $this->prophesize(RelationElementInterface::class);
        $relationElement->hasProperty('hasFile')->willReturn(true);
        $relationElement->getProperty('hasFile')->willReturn(false);

        $cypherFragment = $this->prophesize(RelationInterface::class);
        $cypherFragment->addProperty('hasFile', false)->shouldBeCalledOnce();
        $mongoFragment = $this->prophesize(MongoDocumentInterface::class);
        $mongoFragment->addProperty('hasFile', false)->shouldBeCalledOnce();
        $elasticFragment = $this->prophesize(ElasticDocumentInterface::class);
        $elasticFragment->addProperty('hasFile', false)->shouldBeCalledOnce();

        $event = new RelationElementFragmentizeEvent(
            $relationElement->reveal(),
            $cypherFragment->reveal(),
            $mongoFragment->reveal(),
            $elasticFragment->reveal(),
        );

        (new HasFilePropertyElementFragmentizeEventListener())->onRelationElementFragmentizeEvent($event);
    }

    public function testRelationEventDoesNothingWhenPropertyIsMissing(): void
    {
        $relationElement = $this->prophesize(RelationElementInterface::class);
        $relationElement->hasProperty('hasFile')->willReturn(false);

        $cypherFragment = $this->prophesize(RelationInterface::class);
        $mongoFragment = $this->prophesize(MongoDocumentInterface::class);
        $elasticFragment = $this->prophesize(ElasticDocumentInterface::class);

        $event = new RelationElementFragmentizeEvent(
            $relationElement->reveal(),
            $cypherFragment->reveal(),
            $mongoFragment->reveal(),
            $elasticFragment->reveal(),
        );

        (new HasFilePropertyElementFragmentizeEventListener())->onRelationElementFragmentizeEvent($event);
        $this->addToAssertionCount(1);
    }
}
