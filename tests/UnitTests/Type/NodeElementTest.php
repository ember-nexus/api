<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Type;

use App\Type\NodeElement;
use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class NodeElementTest extends TestCase
{
    public function testPropertiesTrait(): void
    {
        $nodeElement = new NodeElement();

        $this->assertEmpty($nodeElement->getProperties());
        $this->assertFalse($nodeElement->hasProperty('test'));

        $nodeElement->addProperty('test', 'value');

        $this->assertCount(1, $nodeElement->getProperties());
        $this->assertTrue($nodeElement->hasProperty('test'));
        $this->assertSame('value', $nodeElement->getProperty('test'));

        $nodeElement->removeProperty('test');
        $this->assertEmpty($nodeElement->getProperties());

        $nodeElement->addProperties([
            'a' => 'value',
            'b' => 'value',
        ]);

        $properties = $nodeElement->getProperties();
        $this->assertCount(2, $properties);

        $this->assertArrayHasKey('a', $properties);
        $this->assertArrayHasKey('b', $properties);

        $nodeElement->removeProperties();

        $this->assertEmpty($nodeElement->getProperties());
    }

    public function testPropertiesTraitGetPropertyWhichDoesNotExist(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $nodeElement = new NodeElement();

        try {
            $nodeElement->getProperty('propertyWhichDoesNotExist');
        } catch (Exception $exception) {
            $this->assertSame('Undefined array key "propertyWhichDoesNotExist"', $exception->getMessage());
        }

        $this->assertTrue(true);
    }

    public function testPropertiesTraitRemovePropertyWhichDoesNotExist(): void
    {
        $nodeElement = new NodeElement();

        $this->expectNotToPerformAssertions();

        $nodeElement->removeProperty('propertyWhichDoesNotExist');
    }

    public function testIdentifierTrait(): void
    {
        $nodeElement = new NodeElement();

        $this->assertNull($nodeElement->getIdentifier());

        $uuid = Uuid::fromString('e6f6ef39-60b4-47aa-8a7f-091502943815');
        $nodeElement->setIdentifier($uuid);
        $this->assertSame($uuid, $nodeElement->getIdentifier());

        $nodeElement->setIdentifier(null);
        $this->assertNull($nodeElement->getIdentifier());
    }

    public function testLabel(): void
    {
        $nodeElement = new NodeElement();

        $this->assertNull($nodeElement->getLabel());

        $nodeElement->setLabel('SomeLabel');

        $this->assertSame('SomeLabel', $nodeElement->getLabel());

        $nodeElement->setLabel(null);

        $this->assertNull($nodeElement->getLabel());
    }
}
