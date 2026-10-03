<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\DigestService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(DigestService::class)]
class DigestServiceTest extends TestCase
{
    // sha-256 of "hello"
    private const string HEX_HASH = '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824';
    private const string BASE64_DIGEST = 'LPJNul+wow4m6DsqxbninhsWHlwfp0JecwQzYpOLmCQ=';

    public function testFormatDigestHeaderValue(): void
    {
        $service = new DigestService();

        $this->assertSame(
            sprintf('sha-256=:%s:', self::BASE64_DIGEST),
            $service->formatDigestHeaderValue(self::HEX_HASH)
        );
    }

    public function testParseSha256HexFromHeaderValue(): void
    {
        $service = new DigestService();

        $hash = $service->parseSha256HexFromHeaderValue(sprintf('sha-256=:%s:', self::BASE64_DIGEST));

        $this->assertSame(self::HEX_HASH, $hash);
    }

    public function testRoundTripsThroughFormatAndParse(): void
    {
        $service = new DigestService();

        $headerValue = $service->formatDigestHeaderValue(self::HEX_HASH);
        $parsedHash = $service->parseSha256HexFromHeaderValue($headerValue);

        $this->assertSame(self::HEX_HASH, $parsedHash);
    }

    public function testReturnsNullForUnsupportedAlgorithmOnly(): void
    {
        $service = new DigestService();

        $this->assertNull($service->parseSha256HexFromHeaderValue('md5=:1B2M2Y8AsgTpgAmY7PhCfg==:'));
    }

    public function testPicksSha256AmongMultipleAlgorithms(): void
    {
        $service = new DigestService();

        $hash = $service->parseSha256HexFromHeaderValue(sprintf('md5=:1B2M2Y8AsgTpgAmY7PhCfg==:, sha-256=:%s:', self::BASE64_DIGEST));

        $this->assertSame(self::HEX_HASH, $hash);
    }

    public function testReturnsNullForMalformedHeaderValue(): void
    {
        $service = new DigestService();

        $this->assertNull($service->parseSha256HexFromHeaderValue('not a digest header at all'));
    }

    public function testReturnsNullForInvalidBase64(): void
    {
        $service = new DigestService();

        $this->assertNull($service->parseSha256HexFromHeaderValue('sha-256=:not-valid-base64!!!:'));
    }

    public function testReturnsNullForWrongLengthDigest(): void
    {
        $service = new DigestService();

        // valid base64, but decodes to fewer than 32 bytes
        $this->assertNull($service->parseSha256HexFromHeaderValue('sha-256=:dG9vc2hvcnQ=:'));
    }
}
