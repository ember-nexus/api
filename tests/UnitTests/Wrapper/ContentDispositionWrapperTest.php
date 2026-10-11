<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Wrapper;

use App\Wrapper\ContentDispositionWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ContentDispositionWrapper::class)]
class ContentDispositionWrapperTest extends TestCase
{
    public function testFilenameIsParsed(): void
    {
        $this->assertSame('report.pdf', (new ContentDispositionWrapper())->parseContentDisposition('attachment; filename="report.pdf"'));
    }

    public function testExtendedFilenameIsPreferred(): void
    {
        $this->assertSame('€ rates.txt', (new ContentDispositionWrapper())->parseContentDisposition("attachment; filename=\"EURO rates.txt\"; filename*=UTF-8''%E2%82%AC%20rates.txt"));
    }

    public function testMissingFilenameReturnsNull(): void
    {
        $this->assertNull((new ContentDispositionWrapper())->parseContentDisposition('attachment'));
    }
}
