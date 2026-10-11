<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\Request;

use App\Type\Request\ResumableUploadRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(ResumableUploadRequest::class)]
class ResumableUploadRequestTest extends TestCase
{
    public function testResumableUploadRequest(): void
    {
        $resumableUploadRequest = new ResumableUploadRequest(
            Uuid::fromString('d62f9b55-5577-4e2d-8884-d678d60fa7ba'),
            'some content',
            null,
            null,
            12
        );

        $this->assertSame('d62f9b55-5577-4e2d-8884-d678d60fa7ba', (string) $resumableUploadRequest->getElementId());
        $this->assertSame('some content', $resumableUploadRequest->getContent());
        $this->assertNull($resumableUploadRequest->isUploadComplete());
        $this->assertNull($resumableUploadRequest->getUploadLength());
        $this->assertSame(12, $resumableUploadRequest->getContentLength());
        $this->assertSame('bin', $resumableUploadRequest->getExtension());
    }

    public function testResumableUploadRequestDefaults(): void
    {
        $resumableUploadRequest = new ResumableUploadRequest(
            Uuid::fromString('d62f9b55-5577-4e2d-8884-d678d60fa7ba'),
            'some content',
        );

        $this->assertFalse($resumableUploadRequest->isUploadComplete());
        $this->assertNull($resumableUploadRequest->getUploadLength());
        $this->assertNull($resumableUploadRequest->getContentLength());
        $this->assertSame('bin', $resumableUploadRequest->getExtension());
    }
}
