<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Covers the `Repr-Digest` / `Content-Digest` header being an RFC 8941 Dictionary that may declare more than one
 * algorithm: `sha-256` is picked out regardless of its position among other (unsupported) algorithms, a header
 * declaring no supported algorithm at all is treated the same as one without a usable digest, and a header which is
 * not a valid Dictionary of `algorithm=:base64:` members, or which declares the same algorithm more than once, is
 * rejected outright.
 */
class FileDigestMultipleAlgorithmsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string ASSET_PATH = __DIR__.'/../../Asset/file-digest-multiple-algorithms.bin';
    private const string UNSUPPORTED_MEMBER = 'md5=:1B2M2Y8AsgTpgAmY7PhCfg==:';

    private function sha256Member(string $hexHash): string
    {
        return sprintf('sha-256=:%s:', base64_encode(\Safe\hex2bin($hexHash)));
    }

    /**
     * @param array<string, string|int> $headers
     */
    private function upload(string $id, string $path, array $headers): \Psr\Http\Message\ResponseInterface
    {
        return $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $id),
            \Safe\fopen($path, 'r'),
            self::TOKEN,
            array_merge(['Content-Type' => 'application/octet-stream'], $headers)
        );
    }

    public function testUploadSucceedsWhenSha256IsFirstAmongMultipleAlgorithms(): void
    {
        $id = $this->createElement(self::TOKEN, 'digest-multi-sha256-first');
        $this->generateDeterministicFile(80010001, 2048, self::ASSET_PATH);
        $sha256 = $this->sha256Member(hash_file('sha256', self::ASSET_PATH));

        $response = $this->upload($id, self::ASSET_PATH, [
            'Repr-Digest' => sprintf('%s, %s', $sha256, self::UNSUPPORTED_MEMBER),
        ]);
        $this->assertIsCreatedResponse($response, false);
        unlink(self::ASSET_PATH);

        $this->runDeleteRequest(sprintf('/%s', $id), self::TOKEN);
    }

    public function testUploadSucceedsWhenSha256IsLastAmongMultipleAlgorithms(): void
    {
        $id = $this->createElement(self::TOKEN, 'digest-multi-sha256-last');
        $this->generateDeterministicFile(80010002, 2048, self::ASSET_PATH);
        $sha256 = $this->sha256Member(hash_file('sha256', self::ASSET_PATH));

        $response = $this->upload($id, self::ASSET_PATH, [
            'Repr-Digest' => sprintf('%s, %s', self::UNSUPPORTED_MEMBER, $sha256),
        ]);
        $this->assertIsCreatedResponse($response, false);
        unlink(self::ASSET_PATH);

        $this->runDeleteRequest(sprintf('/%s', $id), self::TOKEN);
    }

    public function testUploadIsRejectedWhenNoDeclaredAlgorithmIsSupported(): void
    {
        $id = $this->createElement(self::TOKEN, 'digest-multi-no-supported-algorithm');
        $this->generateDeterministicFile(80010003, 2048, self::ASSET_PATH);

        $response = $this->upload($id, self::ASSET_PATH, [
            'Repr-Digest' => sprintf('%s, unixsum=:MTI=:', self::UNSUPPORTED_MEMBER),
        ]);
        $this->assertIsProblemResponse($response, 400);
        unlink(self::ASSET_PATH);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN), 404);
        $this->runDeleteRequest(sprintf('/%s', $id), self::TOKEN);
    }

    public function testUploadIsRejectedWhenSha256IsDeclaredTwice(): void
    {
        $id = $this->createElement(self::TOKEN, 'digest-multi-duplicate-sha256');
        $this->generateDeterministicFile(80010004, 2048, self::ASSET_PATH);
        $sha256 = $this->sha256Member(hash_file('sha256', self::ASSET_PATH));

        $response = $this->upload($id, self::ASSET_PATH, [
            'Repr-Digest' => sprintf('%s, %s', $sha256, $sha256),
        ]);
        $this->assertIsProblemResponse($response, 400);
        unlink(self::ASSET_PATH);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN), 404);
        $this->runDeleteRequest(sprintf('/%s', $id), self::TOKEN);
    }

    public function testUploadIsRejectedWhenDigestHeaderIsUnparseable(): void
    {
        $id = $this->createElement(self::TOKEN, 'digest-multi-unparseable');
        $this->generateDeterministicFile(80010005, 2048, self::ASSET_PATH);

        $response = $this->upload($id, self::ASSET_PATH, [
            'Repr-Digest' => 'not a digest header at all',
        ]);
        $this->assertIsProblemResponse($response, 400);
        unlink(self::ASSET_PATH);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN), 404);
        $this->runDeleteRequest(sprintf('/%s', $id), self::TOKEN);
    }
}
