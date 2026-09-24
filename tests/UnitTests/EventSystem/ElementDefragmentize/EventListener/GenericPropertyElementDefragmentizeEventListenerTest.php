<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementDefragmentize\EventListener;

use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\EventListener\GenericPropertyElementDefragmentizeEventListener;
use App\Type\NodeElement;
use App\Type\RelationElement;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\MongoDataStructures\Type\Document;

#[Small]
#[CoversClass(GenericPropertyElementDefragmentizeEventListener::class)]
class GenericPropertyElementDefragmentizeEventListenerTest extends TestCase
{
    public function testTruePlaceholderIsDroppedWhenMongoHoldsTheValue(): void
    {
        $cypher = new Node();
        $cypher->addProperty('list', true);
        $cypher->addProperty('name', 'abc');
        $mongo = new Document();
        $mongo->addProperty('list', [1, 2]);

        $element = new NodeElement();
        (new GenericPropertyElementDefragmentizeEventListener())
            ->onNodeElementDefragmentizeEvent(new NodeElementDefragmentizeEvent($element, $cypher, $mongo));

        $this->assertSame(['list' => [1, 2], 'name' => 'abc'], $element->getProperties());
    }

    public function testTrueWithoutMongoCounterpartIsKept(): void
    {
        $cypher = new Node();
        $cypher->addProperty('flag', true);

        $element = new NodeElement();
        (new GenericPropertyElementDefragmentizeEventListener())
            ->onNodeElementDefragmentizeEvent(new NodeElementDefragmentizeEvent($element, $cypher, null));

        $this->assertSame(['flag' => true], $element->getProperties());
    }

    public function testFalseIsNotTreatedAsPlaceholder(): void
    {
        $cypher = new Node();
        $cypher->addProperty('list', false);
        $mongo = new Document();
        $mongo->addProperty('list', [1]);

        $element = new NodeElement();
        (new GenericPropertyElementDefragmentizeEventListener())
            ->onNodeElementDefragmentizeEvent(new NodeElementDefragmentizeEvent($element, $cypher, $mongo));

        $this->assertFalse($element->getProperties()['list']);
    }

    public function testBsonValuesAreConvertedToArrays(): void
    {
        $cypher = new Relation();
        $mongo = new Document();
        $mongo->addProperty('list', new BSONArray([1, 2]));
        $mongo->addProperty('map', new BSONDocument(['a' => 'b']));

        $element = new RelationElement();
        (new GenericPropertyElementDefragmentizeEventListener())
            ->onRelationElementDefragmentizeEvent(new RelationElementDefragmentizeEvent($element, $cypher, $mongo));

        $this->assertSame(['list' => [1, 2], 'map' => ['a' => 'b']], $element->getProperties());
    }

    public function testReservedPropertiesAndNullsAreRemoved(): void
    {
        $cypher = new Node();
        $cypher->addProperty('id', 'x');
        $cypher->addProperty('nothing', null);
        $cypher->addProperty('kept', 1);

        $element = new NodeElement();
        (new GenericPropertyElementDefragmentizeEventListener())
            ->onNodeElementDefragmentizeEvent(new NodeElementDefragmentizeEvent($element, $cypher, null));

        $this->assertSame(['kept' => 1], $element->getProperties());
    }
}
