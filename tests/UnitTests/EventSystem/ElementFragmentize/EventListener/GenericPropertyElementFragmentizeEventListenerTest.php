<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\EventListener\GenericPropertyElementFragmentizeEventListener;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

#[Small]
#[CoversClass(GenericPropertyElementFragmentizeEventListener::class)]
class GenericPropertyElementFragmentizeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array<string, mixed> $properties
     */
    private function fragmentizeNode(array $properties): NodeElementFragmentizeEvent
    {
        $element = new NodeElement();
        $element->addProperties($properties);
        $event = new NodeElementFragmentizeEvent($element, new Node(), new MongoDocument(), new ElasticDocument());
        (new GenericPropertyElementFragmentizeEventListener(
            $this->prophesize(Server500InternalServerErrorExceptionFactory::class)->reveal()
        ))->onNodeElementFragmentizeEvent($event);

        return $event;
    }

    public function testArrayPropertyGetsTruePlaceholderInNeo4jAndValueInMongo(): void
    {
        $event = $this->fragmentizeNode(['list' => [1, 2], 'map' => ['a' => 'b']]);

        $this->assertTrue($event->getCypherFragment()->getProperties()['list']);
        $this->assertTrue($event->getCypherFragment()->getProperties()['map']);
        $this->assertSame([1, 2], $event->getMongoFragment()->getProperties()['list']);
        $this->assertSame(['a' => 'b'], $event->getMongoFragment()->getProperties()['map']);
    }

    public function testObjectPropertyGetsTruePlaceholderInNeo4jAndValueInMongo(): void
    {
        $object = new stdClass();
        $event = $this->fragmentizeNode(['object' => $object]);

        $this->assertTrue($event->getCypherFragment()->getProperties()['object']);
        $this->assertSame($object, $event->getMongoFragment()->getProperties()['object']);
    }

    public function testScalarPropertiesStayInNeo4jOnly(): void
    {
        $event = $this->fragmentizeNode(['int' => 5, 'flag' => false, 'text' => 'abc']);

        $this->assertSame(['int' => 5, 'flag' => false, 'text' => 'abc'], $event->getCypherFragment()->getProperties());
        $this->assertSame([], $event->getMongoFragment()->getProperties());
    }

    public function testLongStringIsMovedToMongo(): void
    {
        $long = str_repeat('a', 1025);
        $event = $this->fragmentizeNode(['text' => $long]);

        $this->assertArrayNotHasKey('text', $event->getCypherFragment()->getProperties());
        $this->assertSame($long, $event->getMongoFragment()->getProperties()['text']);
    }

    public function testRelationEventAlsoUsesPlaceholder(): void
    {
        $element = new RelationElement();
        $element->addProperties(['list' => [1]]);
        $event = new RelationElementFragmentizeEvent($element, new Relation(), new MongoDocument(), new ElasticDocument());
        (new GenericPropertyElementFragmentizeEventListener(
            $this->prophesize(Server500InternalServerErrorExceptionFactory::class)->reveal()
        ))->onRelationElementFragmentizeEvent($event);

        $this->assertTrue($event->getCypherFragment()->getProperties()['list']);
        $this->assertSame([1], $event->getMongoFragment()->getProperties()['list']);
    }
}
