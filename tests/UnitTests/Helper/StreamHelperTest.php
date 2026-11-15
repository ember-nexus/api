<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Helper;

use App\Helper\StreamHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(StreamHelper::class)]
class StreamHelperTest extends TestCase
{
    public function testGetSize(): void
    {
        $stream = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($stream, 'hello');

        $this->assertSame(5, StreamHelper::getSize($stream));

        \Safe\fclose($stream);
    }
}
