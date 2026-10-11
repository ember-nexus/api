<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\S3;

use App\Type\S3\MergeFileChunksOperation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(MergeFileChunksOperation::class)]
class MergeFileChunksOperationTest extends TestCase
{
    public function testMergeFileChunksOperation(): void
    {
        $mergeFileChunksOperation = new MergeFileChunksOperation(
            'uploadBucket',
            ['uploadKey1', 'uploadKey2', 'uploadKey3'],
            'storageBucket',
            'previousStorageKey',
            'storageKey'
        );

        $this->assertSame('uploadBucket', $mergeFileChunksOperation->getUploadBucket());
        $this->assertSame(['uploadKey1', 'uploadKey2', 'uploadKey3'], $mergeFileChunksOperation->getUploadKeys());
        $this->assertSame('storageBucket', $mergeFileChunksOperation->getStorageBucket());
        $this->assertSame('previousStorageKey', $mergeFileChunksOperation->getPreviousStorageKey());
        $this->assertSame('storageKey', $mergeFileChunksOperation->getStorageKey());
    }
}
