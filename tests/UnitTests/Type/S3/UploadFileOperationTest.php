<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\S3;

use App\Type\S3\UploadFileOperation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(UploadFileOperation::class)]
class UploadFileOperationTest extends TestCase
{
    public function testUploadFileOperation(): void
    {
        $uploadFileOperation = new UploadFileOperation(
            'uploadBucket',
            'uploadKey',
            'storageBucket',
            'previousStorageKey',
            'storageKey',
            'some content',
            12,
            'some mimetype'
        );

        $this->assertSame('uploadBucket', $uploadFileOperation->getUploadBucket());
        $this->assertSame('uploadKey', $uploadFileOperation->getUploadKey());
        $this->assertSame('storageBucket', $uploadFileOperation->getStorageBucket());
        $this->assertSame('previousStorageKey', $uploadFileOperation->getPreviousStorageKey());
        $this->assertSame('storageKey', $uploadFileOperation->getStorageKey());
        $this->assertSame('some content', $uploadFileOperation->getContent());
        $this->assertSame(12, $uploadFileOperation->getContentLength());
        $this->assertSame('some mimetype', $uploadFileOperation->getMimeType());
    }
}
