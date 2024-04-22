<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Type;

use App\Type\RelationElement;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RelationElementTest extends TestCase
{
    public function testPropertiesTrait(): void
    {
        $relationElement = new RelationElement();

        $this->assertEmpty($relationElement->getProperties());
        $this->assertFalse($relationElement->hasProperty('test'));

        $relationElement->addProperty('test', 'value');

        $this->assertCount(1, $relationElement->getProperties());
        $this->assertTrue($relationElement->hasProperty('test'));
        $this->assertSame('value', $relationElement->getProperty('test'));

        $relationElement->removeProperty('test');
        $this->assertEmpty($relationElement->getProperties());

        $relationElement->addProperties([
            'a' => 'value',
            'b' => 'value',
        ]);

        $properties = $relationElement->getProperties();
        $this->assertCount(2, $properties);

        $this->assertArrayHasKey('a', $properties);
        $this->assertArrayHasKey('b', $properties);

        $relationElement->removeProperties();

        $this->assertEmpty($relationElement->getProperties());
    }

    public function testPropertiesTraitGetPropertyWhichDoesNotExist(): void
    {
        $relationElement = new RelationElement();

        $this->expectExceptionMessage('Undefined array key "propertyWhichDoesNotExist".');
        $relationElement->getProperty('propertyWhichDoesNotExist');
    }

    public function testPropertiesTraitRemovePropertyWhichDoesNotExist(): void
    {
        $relationElement = new RelationElement();

        $this->expectNotToPerformAssertions();

        $relationElement->removeProperty('propertyWhichDoesNotExist');
    }

    public function testIdTrait(): void
    {
        $relationElement = new RelationElement();

        $this->assertNull($relationElement->getId());

        $id = Uuid::fromString('e6f6ef39-60b4-47aa-8a7f-091502943815');
        $relationElement->setId($id);
        $this->assertSame($id, $relationElement->getId());

        $relationElement->setId(null);
        $this->assertNull($relationElement->getId());
    }

    public function testType(): void
    {
        $relationElement = new RelationElement();

        $this->assertNull($relationElement->getType());

        $relationElement->setType('SOME_TYPE');

        $this->assertSame('SOME_TYPE', $relationElement->getType());

        $relationElement->setType(null);

        $this->assertNull($relationElement->getType());
    }

    public function testStart(): void
    {
        $relationElement = new RelationElement();

        $this->assertNull($relationElement->getStart());

        $id = Uuid::fromString('8790d9de-453c-44af-9bd4-ce2515c5f164');

        $relationElement->setStart($id);

        $this->assertSame($id, $relationElement->getStart());

        $relationElement->setStart(null);

        $this->assertNull($relationElement->getStart());
    }

    public function testEnd(): void
    {
        $relationElement = new RelationElement();

        $this->assertNull($relationElement->getEnd());

        $id = Uuid::fromString('8790d9de-453c-44af-9bd4-ce2515c5f164');

        $relationElement->setEnd($id);

        $this->assertSame($id, $relationElement->getEnd());

        $relationElement->setEnd(null);

        $this->assertNull($relationElement->getEnd());
    }
}
