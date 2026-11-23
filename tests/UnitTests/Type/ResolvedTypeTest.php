<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\ElementKind;
use App\Type\MphfOverlaySlot;
use App\Type\ResolvedType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ResolvedType::class)]
#[CoversClass(MphfOverlaySlot::class)]
class ResolvedTypeTest extends TestCase
{
    public function testNode(): void
    {
        $type = new ResolvedType(ElementKind::NODE, 'Data');

        $this->assertTrue($type->isNode());
        $this->assertFalse($type->isRelation());
        $this->assertSame('Data', $type->getType());
        $this->assertSame(ElementKind::NODE, $type->getKind());
    }

    public function testRelation(): void
    {
        $type = new ResolvedType(ElementKind::RELATION, 'OWNS');

        $this->assertTrue($type->isRelation());
        $this->assertFalse($type->isNode());
    }

    public function testEncodeAndDecodeRoundtrip(): void
    {
        $node = new ResolvedType(ElementKind::NODE, 'Data');
        $relation = new ResolvedType(ElementKind::RELATION, 'OWNS');

        $this->assertSame('n:Data', $node->encode());
        $this->assertSame('r:OWNS', $relation->encode());
        $this->assertEquals($node, ResolvedType::decode('n:Data'));
        $this->assertEquals($relation, ResolvedType::decode('r:OWNS'));
        $this->assertEquals(new ResolvedType(ElementKind::NODE, 'a:b'), ResolvedType::decode('n:a:b'));
    }

    public function testDecodeRejectsInvalidValues(): void
    {
        $this->assertNull(ResolvedType::decode(''));
        $this->assertNull(ResolvedType::decode('Data'));
        $this->assertNull(ResolvedType::decode('n:'));
        $this->assertNull(ResolvedType::decode('x:Data'));
    }

    public function testOverlaySlotOther(): void
    {
        $this->assertSame(MphfOverlaySlot::B, MphfOverlaySlot::A->other());
        $this->assertSame(MphfOverlaySlot::A, MphfOverlaySlot::B->other());
    }
}
