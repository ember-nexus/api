<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\S3;

use App\Type\S3\FileOperation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(FileOperation::class)]
class FileOperationTest extends TestCase
{
    public function testFileOperation(): void
    {
        $fileOperation = new FileOperation('bucket', 'key');

        $this->assertSame('bucket', $fileOperation->getBucket());
        $this->assertSame('key', $fileOperation->getKey());
    }
}
