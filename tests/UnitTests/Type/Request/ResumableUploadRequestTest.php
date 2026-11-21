<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\Request;

use App\Type\Request\ResumableUploadRequest;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ResumableUploadRequest::class)]
class ResumableUploadRequestTest extends TestCase
{
    public function testResumableUploadRequest(): void
    {
        $resumableUploadRequest = new ResumableUploadRequest(
            Uuid::fromString('02IiNr0G8viOVKl1TYHQu6KTObS99VBTrQ7gg'),
            'some content',
            null,
            null,
            12
        );

        $this->assertSame('02IiNr0G8viOVKl1TYHQu6KTObS99VBTrQ7gg', (string) $resumableUploadRequest->getElementId());
        $this->assertSame('some content', $resumableUploadRequest->getContent());
        $this->assertNull($resumableUploadRequest->isUploadComplete());
        $this->assertNull($resumableUploadRequest->getUploadLength());
        $this->assertSame(12, $resumableUploadRequest->getContentLength());
        $this->assertSame('bin', $resumableUploadRequest->getExtension());
    }

    public function testResumableUploadRequestDefaults(): void
    {
        $resumableUploadRequest = new ResumableUploadRequest(
            Uuid::fromString('02IiNr0G8viOVKl1TYHQu6KTObS99VBTrQ7gg'),
            'some content',
        );

        $this->assertFalse($resumableUploadRequest->isUploadComplete());
        $this->assertNull($resumableUploadRequest->getUploadLength());
        $this->assertNull($resumableUploadRequest->getContentLength());
        $this->assertSame('bin', $resumableUploadRequest->getExtension());
    }
}
