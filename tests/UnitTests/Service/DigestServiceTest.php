<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\DigestService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(DigestService::class)]
class DigestServiceTest extends TestCase
{
    // sha-256 of "hello"
    private const string HEX_HASH = '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824';
    private const string BASE64_DIGEST = 'LPJNul+wow4m6DsqxbninhsWHlwfp0JecwQzYpOLmCQ=';

    private function createService(): DigestService
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.com/exception-detail/400/bad-content');

        return new DigestService(new Client400BadContentExceptionFactory($urlGenerator));
    }

    public function testFormatDigestHeaderValue(): void
    {
        $service = $this->createService();

        $this->assertSame(
            sprintf('sha-256=:%s:', self::BASE64_DIGEST),
            $service->formatDigestHeaderValue(self::HEX_HASH)
        );
    }

    public function testParseSha256HexFromHeaderValue(): void
    {
        $service = $this->createService();

        $hash = $service->parseSha256HexFromHeaderValue(sprintf('sha-256=:%s:', self::BASE64_DIGEST));

        $this->assertSame(self::HEX_HASH, $hash);
    }

    public function testRoundTripsThroughFormatAndParse(): void
    {
        $service = $this->createService();

        $headerValue = $service->formatDigestHeaderValue(self::HEX_HASH);
        $parsedHash = $service->parseSha256HexFromHeaderValue($headerValue);

        $this->assertSame(self::HEX_HASH, $parsedHash);
    }

    public function testReturnsNullForUnsupportedAlgorithmOnly(): void
    {
        $service = $this->createService();

        $this->assertNull($service->parseSha256HexFromHeaderValue('md5=:1B2M2Y8AsgTpgAmY7PhCfg==:'));
    }

    public function testPicksSha256AmongMultipleAlgorithms(): void
    {
        $service = $this->createService();

        $hash = $service->parseSha256HexFromHeaderValue(sprintf('md5=:1B2M2Y8AsgTpgAmY7PhCfg==:, sha-256=:%s:', self::BASE64_DIGEST));

        $this->assertSame(self::HEX_HASH, $hash);
    }

    public function testPicksSha256WhenDeclaredBeforeUnsupportedAlgorithm(): void
    {
        $service = $this->createService();

        $hash = $service->parseSha256HexFromHeaderValue(sprintf('sha-256=:%s:, md5=:1B2M2Y8AsgTpgAmY7PhCfg==:', self::BASE64_DIGEST));

        $this->assertSame(self::HEX_HASH, $hash);
    }

    public function testThrowsForMalformedHeaderValue(): void
    {
        $service = $this->createService();

        $this->expectException(Client400BadContentException::class);
        $service->parseSha256HexFromHeaderValue('not a digest header at all');
    }

    public function testThrowsForInvalidBase64(): void
    {
        $service = $this->createService();

        $this->expectException(Client400BadContentException::class);
        $service->parseSha256HexFromHeaderValue('sha-256=:not-valid-base64!!!:');
    }

    public function testThrowsForWrongLengthDigest(): void
    {
        $service = $this->createService();

        // valid base64, but decodes to fewer than 32 bytes
        $this->expectException(Client400BadContentException::class);
        $service->parseSha256HexFromHeaderValue('sha-256=:dG9vc2hvcnQ=:');
    }

    public function testThrowsForDuplicateSupportedAlgorithm(): void
    {
        $service = $this->createService();

        $this->expectException(Client400BadContentException::class);
        $service->parseSha256HexFromHeaderValue(sprintf('sha-256=:%s:, sha-256=:%s:', self::BASE64_DIGEST, self::BASE64_DIGEST));
    }

    public function testThrowsForDuplicateUnsupportedAlgorithm(): void
    {
        $service = $this->createService();

        $this->expectException(Client400BadContentException::class);
        $service->parseSha256HexFromHeaderValue('md5=:1B2M2Y8AsgTpgAmY7PhCfg==:, md5=:1B2M2Y8AsgTpgAmY7PhCfg==:');
    }

    public function testThrowsForUnparseableMemberAmongOtherwiseValidOnes(): void
    {
        $service = $this->createService();

        $this->expectException(Client400BadContentException::class);
        $service->parseSha256HexFromHeaderValue(sprintf('sha-256=:%s:, not-a-member', self::BASE64_DIGEST));
    }
}
