<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\S3;

use App\Type\S3\UploadFileChunkOperation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(UploadFileChunkOperation::class)]
class UploadFileChunkOperationTest extends TestCase
{
    public function testUploadFileChunkOperation(): void
    {
        $uploadFileChunkOperation = new UploadFileChunkOperation(
            'uploadBucket',
            'uploadKey',
            'some content',
            12,
            'some mimetype'
        );

        $this->assertSame('uploadBucket', $uploadFileChunkOperation->getUploadBucket());
        $this->assertSame('uploadKey', $uploadFileChunkOperation->getUploadKey());
        $this->assertSame('some content', $uploadFileChunkOperation->getContent());
        $this->assertSame(12, $uploadFileChunkOperation->getContentLength());
        $this->assertSame('some mimetype', $uploadFileChunkOperation->getMimeType());
    }
}
