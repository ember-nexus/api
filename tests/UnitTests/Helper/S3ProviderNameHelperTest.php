<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Helper;

use App\Helper\S3ProviderNameHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(S3ProviderNameHelper::class)]
class S3ProviderNameHelperTest extends TestCase
{
    public function testMissingHeaderFallsBackToUnknown(): void
    {
        $this->assertSame('Unknown', S3ProviderNameHelper::normalize(null));
    }

    public function testBlankHeaderFallsBackToUnknown(): void
    {
        $this->assertSame('Unknown', S3ProviderNameHelper::normalize('   '));
    }

    public function testMinioHeaderIsRecognized(): void
    {
        $this->assertSame('MinIO', S3ProviderNameHelper::normalize('MinIO'));
    }

    public function testVersityGwHeaderIsRecognizedRegardlessOfCase(): void
    {
        $this->assertSame('VersityGW', S3ProviderNameHelper::normalize('VERSITYGW'));
        $this->assertSame('VersityGW', S3ProviderNameHelper::normalize('versitygw'));
    }

    public function testSeaweedFsHeaderIsRecognizedAndExtraDetailsAreDropped(): void
    {
        $this->assertSame('SeaweedFS', S3ProviderNameHelper::normalize('SeaweedFS 30GB 4.48'));
    }

    public function testUnknownProviderFallsBackToSanitizedRawHeader(): void
    {
        $this->assertSame(
            'AmazonS3 rm -rf tmp',
            S3ProviderNameHelper::normalize('AmazonS3 $(rm -rf /tmp)')
        );
    }

    public function testUnknownProviderHeaderThatBecomesEmptyAfterSanitizationFallsBackToUnknown(): void
    {
        $this->assertSame('Unknown', S3ProviderNameHelper::normalize("\x07\x1b!@#$%^&*()"));
    }

    public function testUnknownProviderHeaderIsTruncatedToSixtyFourCharacters(): void
    {
        $provider = S3ProviderNameHelper::normalize(str_repeat('a', 100));

        $this->assertSame(64, strlen($provider));
        $this->assertSame(str_repeat('a', 64), $provider);
    }
}
