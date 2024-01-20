<?php

namespace App\tests\UnitTests\Type;

use App\Type\Etag;
use PHPUnit\Framework\TestCase;

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
