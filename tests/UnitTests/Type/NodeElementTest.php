<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\NodeElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(NodeElement::class)]
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
        $nodeElement = new NodeElement();

        $this->expectExceptionMessage('Undefined array key "propertyWhichDoesNotExist".');
        $nodeElement->getProperty('propertyWhichDoesNotExist');
    }

    public function testPropertiesTraitRemovePropertyWhichDoesNotExist(): void
    {
        $nodeElement = new NodeElement();

        $this->expectNotToPerformAssertions();

        $nodeElement->removeProperty('propertyWhichDoesNotExist');
    }

    public function testIdTrait(): void
    {
        $nodeElement = new NodeElement();

        $this->assertNull($nodeElement->getId());

        $id = Uuid::fromString('e6f6ef39-60b4-47aa-8a7f-091502943815');
        $nodeElement->setId($id);
        $this->assertSame($id, $nodeElement->getId());

        $nodeElement->setId(null);
        $this->assertNull($nodeElement->getId());
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
