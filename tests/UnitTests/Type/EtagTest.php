<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\Etag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Etag::class)]
class EtagTest extends TestCase
{
    public function testEtag(): void
    {
        $etag = new Etag('someEtag');
        $this->assertSame('someEtag', $etag->getEtag());
        $this->assertSame('someEtag', (string) $etag);
        $this->assertSame($etag->getEtag(), (string) $etag);
    }
}
