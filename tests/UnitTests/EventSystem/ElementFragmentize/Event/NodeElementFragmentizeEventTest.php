<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFragmentize\Event;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\Type\NodeElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

#[Small]
#[CoversClass(NodeElementFragmentizeEvent::class)]
class NodeElementFragmentizeEventTest extends TestCase
{
    public function testNodeElementFragmentizeEvent(): void
    {
        $nodeElement = new NodeElement();
        $cypherFragment = new Node();
        $mongoFragment = new MongoDocument();
        $elasticFragment = new ElasticDocument();
        $fileFragment = null;

        $nodeElementFragmentizeEvent = new NodeElementFragmentizeEvent(
            $nodeElement,
            $cypherFragment,
            $mongoFragment,
            $elasticFragment,
            $fileFragment
        );

        $this->assertSame($nodeElement, $nodeElementFragmentizeEvent->getNodeElement());
        $this->assertSame($cypherFragment, $nodeElementFragmentizeEvent->getCypherFragment());
        $this->assertSame($mongoFragment, $nodeElementFragmentizeEvent->getMongoFragment());
        $this->assertSame($elasticFragment, $nodeElementFragmentizeEvent->getElasticFragment());
        $this->assertSame($fileFragment, $nodeElementFragmentizeEvent->getFileFragment());
    }
}
