<?php

namespace App\tests\UnitTests\Type;

use App\Type\RelationElement;
use Exception;
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
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $relationElement = new RelationElement();

        try {
            $relationElement->getProperty('propertyWhichDoesNotExist');
        } catch (Exception $exception) {
            $this->assertSame('Undefined array key "propertyWhichDoesNotExist"', $exception->getMessage());
        }

        $this->assertTrue(true);
    }

    public function testPropertiesTraitRemovePropertyWhichDoesNotExist(): void
    {
        $relationElement = new RelationElement();

        $this->expectNotToPerformAssertions();

        $relationElement->removeProperty('propertyWhichDoesNotExist');
    }

    public function testIdentifierTrait(): void
    {
        $relationElement = new RelationElement();

        $this->assertNull($relationElement->getIdentifier());

        $uuid = Uuid::fromString('e6f6ef39-60b4-47aa-8a7f-091502943815');
        $relationElement->setIdentifier($uuid);
        $this->assertSame($uuid, $relationElement->getIdentifier());

        $relationElement->setIdentifier(null);
        $this->assertNull($relationElement->getIdentifier());
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

        $uuid = Uuid::fromString('8790d9de-453c-44af-9bd4-ce2515c5f164');

        $relationElement->setStart($uuid);

        $this->assertSame($uuid, $relationElement->getStart());

        $relationElement->setStart(null);

        $this->assertNull($relationElement->getStart());
    }

    public function testEnd(): void
    {
        $relationElement = new RelationElement();

        $this->assertNull($relationElement->getEnd());

        $uuid = Uuid::fromString('8790d9de-453c-44af-9bd4-ce2515c5f164');

        $relationElement->setEnd($uuid);

        $this->assertSame($uuid, $relationElement->getEnd());

        $relationElement->setEnd(null);

        $this->assertNull($relationElement->getEnd());
    }
}
