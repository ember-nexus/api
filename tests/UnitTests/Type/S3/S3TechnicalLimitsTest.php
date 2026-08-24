<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\S3;

use App\Type\S3\S3TechnicalLimits;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(S3TechnicalLimits::class)]
class S3TechnicalLimitsTest extends TestCase
{
    public function testValuesMatchStandardAwsS3MultipartUploadLimits(): void
    {
        $limits = new S3TechnicalLimits();

        $this->assertSame(5 * 1024 * 1024, $limits->getMinChunkSizeInBytes());
        $this->assertSame(10_000, $limits->getMaxChunkCount());
        $this->assertSame(5 * 1024 * 1024 * 1024 * 1024, $limits->getMaxObjectSizeInBytes());
    }
}
