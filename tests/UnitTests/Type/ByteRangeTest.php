<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\ByteRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ByteRange::class)]
class ByteRangeTest extends TestCase
{
    public function testGetters(): void
    {
        $range = new ByteRange(10, 20, 100);

        $this->assertSame(10, $range->getStart());
        $this->assertSame(20, $range->getEnd());
        $this->assertSame(100, $range->getTotalLength());
    }

    public function testLengthIsInclusive(): void
    {
        $range = new ByteRange(0, 9, 100);

        $this->assertSame(10, $range->getLength());
    }

    public function testLengthOfSingleByteRange(): void
    {
        $range = new ByteRange(5, 5, 100);

        $this->assertSame(1, $range->getLength());
    }

    public function testLengthCoveringFullResource(): void
    {
        $range = new ByteRange(0, 99, 100);

        $this->assertSame(100, $range->getLength());
    }
}
