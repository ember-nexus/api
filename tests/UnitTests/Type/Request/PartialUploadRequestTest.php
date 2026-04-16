<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\Request;

use App\Type\Request\PartialUploadRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(PartialUploadRequest::class)]
class PartialUploadRequestTest extends TestCase
{
    public function testPartialUploadRequest(): void
    {
        $partialUploadRequest = new PartialUploadRequest(
            'some content',
            'application/partial-upload',
            321,
            true
        );

        $this->assertSame('some content', $partialUploadRequest->getContent());
        $this->assertSame('application/partial-upload', $partialUploadRequest->getContentType());
        $this->assertSame(321, $partialUploadRequest->getUploadOffset());
        $this->assertTrue($partialUploadRequest->isUploadComplete());
        $this->assertNull($partialUploadRequest->getContentLength());
    }
}
