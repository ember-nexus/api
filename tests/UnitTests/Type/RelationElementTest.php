<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\RelationElement;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(RelationElement::class)]
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

        $id = Uuid::fromString('02IiNr0G8viOVKl9f5FjzIeTKZPCtL4rbxhUb');
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

        $id = Uuid::fromString('02IiNr0G8viOVKkPDWBKd4gdtqNHPgzvd2wVe');

        $relationElement->setStart($id);

        $this->assertSame($id, $relationElement->getStart());

        $relationElement->setStart(null);

        $this->assertNull($relationElement->getStart());
    }

    public function testEnd(): void
    {
        $relationElement = new RelationElement();

        $this->assertNull($relationElement->getEnd());

        $id = Uuid::fromString('02IiNr0G8viOVKkPDWBKd4gdtqNHPgzvd2wVe');

        $relationElement->setEnd($id);

        $this->assertSame($id, $relationElement->getEnd());

        $relationElement->setEnd(null);

        $this->assertNull($relationElement->getEnd());
    }
}
