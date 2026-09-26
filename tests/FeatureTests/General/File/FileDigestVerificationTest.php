<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * End-to-end checks of client-supplied `Repr-Digest` / `Content-Digest` request headers, for single request uploads
 * (POST and PUT) and for the completion of resumable uploads, on nodes and on relations.
 *
 * A correct digest must store the file (verified by content and by the stored hash), a wrong digest must answer 400
 * and leave the element untouched; a wrong digest at the end of a resumable upload also discards the upload.
 */
class FileDigestVerificationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;
    private const int RESUMABLE_FILE_SIZE = self::CHUNK_SIZE + 1024 * 1024;
    private const string ASSET_PATH = __DIR__.'/../../Asset/file-digest-verification.bin';

    /**
     * @return array<string, array{0: bool}>
     */
    public static function targetProvider(): array
    {
        return ['node' => [false], 'relation' => [true]];
    }

    /**
     * @return array<string, array{0: bool, 1: string, 2: string}>
     */
    public static function singleRequestProvider(): array
    {
        $cases = [];
        foreach (['node' => false, 'relation' => true] as $targetName => $onRelation) {
            foreach (['POST', 'PUT'] as $method) {
                foreach (['Repr-Digest', 'Content-Digest'] as $header) {
                    $cases[sprintf('%s %s %s', $targetName, $method, $header)] = [$onRelation, $method, $header];
                }
            }
        }

        return $cases;
    }

    /**
     * @return array{0: string, 1: callable}
     */
    private function createTarget(bool $onRelation, string $name): array
    {
        if ($onRelation) {
            return [
                $this->createEphemeralRelation(self::TOKEN, $name),
                fn (string $id) => $this->deleteEphemeralRelation(self::TOKEN, $id),
            ];
        }
        $id = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));

        return [$id, fn (string $id) => $this->runDeleteRequest(sprintf('/%s', $id), self::TOKEN)];
    }

    private function digestHeaderValue(string $hexHash): string
    {
        return sprintf('sha-256=:%s:', base64_encode(\Safe\hex2bin($hexHash)));
    }

    /**
     * @param array<string, string|int> $headers
     */
    private function upload(string $method, string $id, string $path, array $headers = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->runUploadRequest(
            $method,
            sprintf('/%s/file', $id),
            \Safe\fopen($path, 'r'),
            self::TOKEN,
            array_merge(['Content-Type' => 'application/octet-stream'], $headers)
        );
    }

    private function assertStoredFile(string $id, string $path): void
    {
        $download = $this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN);
        $this->assertSame(200, $download->getStatusCode());
        $this->assertSame(hash_file('sha256', $path), hash('sha256', (string) $download->getBody()));

        $element = $this->getBody($this->runGetRequest(sprintf('/%s', $id), self::TOKEN));
        $this->assertSame(hash_file('sha256', $path), $element['file']['hash']['sha256']);
        $this->assertSame(filesize($path), $element['file']['contentLength']);
    }

    #[DataProvider('singleRequestProvider')]
    public function testSingleRequestWithCorrectDigestStoresFile(bool $onRelation, string $method, string $header): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-correct');
        $this->generateDeterministicFile(70010001, 4096, self::ASSET_PATH);

        $response = $this->upload($method, $id, self::ASSET_PATH, [
            $header => $this->digestHeaderValue(hash_file('sha256', self::ASSET_PATH)),
        ]);
        $this->assertIsCreatedResponse($response, false);
        $this->assertStoredFile($id, self::ASSET_PATH);

        unlink(self::ASSET_PATH);
        $cleanup($id);
    }

    #[DataProvider('singleRequestProvider')]
    public function testSingleRequestWithWrongDigestLeavesElementWithoutFile(bool $onRelation, string $method, string $header): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-wrong');
        $this->generateDeterministicFile(70010002, 4096, self::ASSET_PATH);

        $response = $this->upload($method, $id, self::ASSET_PATH, [
            $header => $this->digestHeaderValue(str_repeat('ab', 32)),
        ]);
        $this->assertIsProblemResponse($response, 400);
        unlink(self::ASSET_PATH);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN), 404);
        $element = $this->getBody($this->runGetRequest(sprintf('/%s', $id), self::TOKEN));
        $this->assertArrayNotHasKey('file', $element);
        $this->assertFalse($element['data']['hasFile'] ?? false);

        $cleanup($id);
    }

    /**
     * @return array<string, array{0: bool, 1: string}>
     */
    public static function singleRequestMethodProvider(): array
    {
        $cases = [];
        foreach (['node' => false, 'relation' => true] as $targetName => $onRelation) {
            foreach (['POST', 'PUT'] as $method) {
                $cases[sprintf('%s %s', $targetName, $method)] = [$onRelation, $method];
            }
        }

        return $cases;
    }

    /**
     * The body of a single request is the whole file, so `Repr-Digest` and `Content-Digest` are the same hash and
     * both are accepted together.
     */
    #[DataProvider('singleRequestMethodProvider')]
    public function testSingleRequestWithCorrectReprAndContentDigestStoresFile(bool $onRelation, string $method): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-both-correct');
        $this->generateDeterministicFile(70010009, 4096, self::ASSET_PATH);
        $digest = $this->digestHeaderValue(hash_file('sha256', self::ASSET_PATH));

        $response = $this->upload($method, $id, self::ASSET_PATH, [
            'Repr-Digest' => $digest,
            'Content-Digest' => $digest,
        ]);
        $this->assertIsCreatedResponse($response, false);
        $this->assertStoredFile($id, self::ASSET_PATH);

        unlink(self::ASSET_PATH);
        $cleanup($id);
    }

    /**
     * @return array<string, array{0: bool, 1: string, 2: string}>
     */
    public static function singleRequestWrongHeaderProvider(): array
    {
        $cases = [];
        foreach (['node' => false, 'relation' => true] as $targetName => $onRelation) {
            foreach (['POST', 'PUT'] as $method) {
                foreach (['Repr-Digest', 'Content-Digest'] as $wrongHeader) {
                    $cases[sprintf('%s %s wrong %s', $targetName, $method, $wrongHeader)] = [$onRelation, $method, $wrongHeader];
                }
            }
        }

        return $cases;
    }

    /**
     * Every supplied digest has to match, a correct one does not excuse a wrong one.
     */
    #[DataProvider('singleRequestWrongHeaderProvider')]
    public function testSingleRequestWithOneCorrectAndOneWrongDigestLeavesElementWithoutFile(bool $onRelation, string $method, string $wrongHeader): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-one-wrong');
        $this->generateDeterministicFile(70010010, 4096, self::ASSET_PATH);
        $correctHeader = 'Repr-Digest' === $wrongHeader ? 'Content-Digest' : 'Repr-Digest';

        $response = $this->upload($method, $id, self::ASSET_PATH, [
            $correctHeader => $this->digestHeaderValue(hash_file('sha256', self::ASSET_PATH)),
            $wrongHeader => $this->digestHeaderValue(str_repeat('ab', 32)),
        ]);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString($wrongHeader, $this->getBody($response)['detail']);
        unlink(self::ASSET_PATH);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN), 404);
        $this->assertArrayNotHasKey('file', $this->getBody($this->runGetRequest(sprintf('/%s', $id), self::TOKEN)));

        $cleanup($id);
    }

    /**
     * A replacement with one wrong digest keeps the old file, whichever header is wrong.
     */
    #[DataProvider('targetProvider')]
    public function testPutWithOneWrongOfBothDigestsKeepsOldFile(bool $onRelation): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-replace-one-wrong');
        $this->generateDeterministicFile(70010011, 4096, self::ASSET_PATH);
        $this->assertIsCreatedResponse($this->upload('POST', $id, self::ASSET_PATH), false);
        $oldEtag = $this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN)->getHeader('ETag');

        $replacementPath = __DIR__.'/../../Asset/file-digest-verification-replacement.bin';
        $this->generateDeterministicFile(70010012, 8192, $replacementPath);
        $correct = $this->digestHeaderValue(hash_file('sha256', $replacementPath));
        $wrong = $this->digestHeaderValue(str_repeat('ab', 32));
        foreach ([['Repr-Digest' => $correct, 'Content-Digest' => $wrong], ['Repr-Digest' => $wrong, 'Content-Digest' => $correct]] as $headers) {
            $this->assertIsProblemResponse($this->upload('PUT', $id, $replacementPath, $headers), 400);
            $this->assertStoredFile($id, self::ASSET_PATH);
            $this->assertSame($oldEtag, $this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN)->getHeader('ETag'));
        }
        unlink($replacementPath);

        unlink(self::ASSET_PATH);
        $cleanup($id);
    }

    #[DataProvider('targetProvider')]
    public function testPutWithWrongDigestKeepsOldFileAndEtag(bool $onRelation): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-replace');
        $this->generateDeterministicFile(70010003, 4096, self::ASSET_PATH);
        $this->assertIsCreatedResponse($this->upload('POST', $id, self::ASSET_PATH), false);
        $oldEtag = $this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN)->getHeader('ETag');
        $this->assertCount(1, $oldEtag);

        $replacementPath = __DIR__.'/../../Asset/file-digest-verification-replacement.bin';
        $this->generateDeterministicFile(70010004, 8192, $replacementPath);
        $response = $this->upload('PUT', $id, $replacementPath, [
            'Repr-Digest' => $this->digestHeaderValue(hash_file('sha256', self::ASSET_PATH)),
        ]);
        $this->assertIsProblemResponse($response, 400);
        unlink($replacementPath);

        $this->assertStoredFile($id, self::ASSET_PATH);
        $this->assertSame($oldEtag, $this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN)->getHeader('ETag'));

        unlink(self::ASSET_PATH);
        $cleanup($id);
    }

    /**
     * @return array{0: string, 1: array<int, string>} upload id and chunk paths
     */
    private function startResumableUpload(string $method, string $id, string $path): array
    {
        $chunks = $this->splitFileToChunks($path, self::CHUNK_SIZE);
        $response = $this->upload($method, $id, $chunks[0], ['Upload-Complete' => '?0']);
        $this->assertNoContentResponse($response, true);

        return [$this->getUuidFromLocation($response), $chunks];
    }

    private function finishResumableUpload(string $uploadId, string $lastChunk, ?string $digest): \Psr\Http\Message\ResponseInterface
    {
        $headers = [
            'Upload-Complete' => '?1',
            'Upload-Offset' => self::CHUNK_SIZE,
            'Content-Type' => 'application/partial-upload',
        ];
        if (null !== $digest) {
            $headers['Repr-Digest'] = $digest;
        }

        return $this->runUploadRequest('PATCH', sprintf('/upload/%s', $uploadId), \Safe\fopen($lastChunk, 'r'), self::TOKEN, $headers);
    }

    #[DataProvider('targetProvider')]
    public function testResumableUploadWithCorrectDigestStoresFile(bool $onRelation): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-resumable-correct');
        $this->generateDeterministicFile(70010005, self::RESUMABLE_FILE_SIZE, self::ASSET_PATH);
        [$uploadId, $chunks] = $this->startResumableUpload('POST', $id, self::ASSET_PATH);

        $response = $this->finishResumableUpload($uploadId, $chunks[1], $this->digestHeaderValue(hash_file('sha256', self::ASSET_PATH)));
        $this->assertNoContentResponse($response);
        $this->assertStoredFile($id, self::ASSET_PATH);

        $this->cleanupChunks($chunks);
        unlink(self::ASSET_PATH);
        $cleanup($id);
    }

    #[DataProvider('targetProvider')]
    public function testResumableUploadWithWrongDigestDiscardsUploadAndKeepsElementWithoutFile(bool $onRelation): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-resumable-wrong');
        $this->generateDeterministicFile(70010006, self::RESUMABLE_FILE_SIZE, self::ASSET_PATH);
        [$uploadId, $chunks] = $this->startResumableUpload('POST', $id, self::ASSET_PATH);

        $response = $this->finishResumableUpload($uploadId, $chunks[1], $this->digestHeaderValue(str_repeat('cd', 32)));
        $this->assertIsProblemResponse($response, 400);

        $this->assertSame(404, $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN)->getStatusCode());
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN), 404);
        $this->assertArrayNotHasKey('file', $this->getBody($this->runGetRequest(sprintf('/%s', $id), self::TOKEN)));

        $this->cleanupChunks($chunks);
        unlink(self::ASSET_PATH);
        $cleanup($id);
    }

    #[DataProvider('targetProvider')]
    public function testResumableReplaceWithWrongDigestDiscardsUploadAndKeepsOldFile(bool $onRelation): void
    {
        [$id, $cleanup] = $this->createTarget($onRelation, 'digest-verification-resumable-replace');
        $oldPath = __DIR__.'/../../Asset/file-digest-verification-old.bin';
        $this->generateDeterministicFile(70010007, 4096, $oldPath);
        $this->assertIsCreatedResponse($this->upload('POST', $id, $oldPath), false);
        $oldEtag = $this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN)->getHeader('ETag');

        $this->generateDeterministicFile(70010008, self::RESUMABLE_FILE_SIZE, self::ASSET_PATH);
        [$uploadId, $chunks] = $this->startResumableUpload('PUT', $id, self::ASSET_PATH);

        $response = $this->finishResumableUpload($uploadId, $chunks[1], $this->digestHeaderValue(str_repeat('ef', 32)));
        $this->assertIsProblemResponse($response, 400);

        $this->assertSame(404, $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN)->getStatusCode());
        $this->assertStoredFile($id, $oldPath);
        $this->assertSame($oldEtag, $this->runGetRequest(sprintf('/%s/file', $id), self::TOKEN)->getHeader('ETag'));

        $this->cleanupChunks($chunks);
        unlink(self::ASSET_PATH);
        unlink($oldPath);
        $cleanup($id);
    }
}
