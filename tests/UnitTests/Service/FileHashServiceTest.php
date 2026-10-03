<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\FileHashService;
use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(FileHashService::class)]
class FileHashServiceTest extends TestCase
{
    public function testCalculatesHashesAcrossMultipleReadChunks(): void
    {
        // larger than the service's read chunk size, so the hash spans several reads
        $content = str_repeat('0123456789abcdef', 200000);
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, $content);
        rewind($resource);

        $service = new FileHashService();
        $hashes = $service->calculateHashesFromResource($resource, ['sha256']);

        $this->assertSame(['sha256' => hash('sha256', $content)], $hashes);
    }

    public function testCalculatesHashOfEmptyResource(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');

        $service = new FileHashService();

        $this->assertSame(['sha256' => hash('sha256', '')], $service->calculateHashesFromResource($resource, ['sha256']));
    }

    public function testCalculatesNoHashesForNoAlgorithms(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');

        $service = new FileHashService();

        $this->assertSame([], $service->calculateHashesFromResource($resource, []));
    }

    public function testGetVerifiableHashesFromArrayFileProperty(): void
    {
        $service = new FileHashService();

        $this->assertSame(
            ['sha256' => 'abcdef'],
            $service->getVerifiableHashesFromFileProperty(['hash' => ['sha256' => 'ABCDEF']])
        );
    }

    public function testGetVerifiableHashesFromArrayAccessFileProperty(): void
    {
        $service = new FileHashService();

        $this->assertSame(
            ['sha256' => 'abc'],
            $service->getVerifiableHashesFromFileProperty(new ArrayObject([
                'hash' => new ArrayObject(['sha256' => 'abc']),
            ]))
        );
    }

    public function testGetVerifiableHashesIgnoresUnsupportedAndMalformedEntries(): void
    {
        $service = new FileHashService();

        $this->assertSame(
            ['sha256' => 'abc'],
            $service->getVerifiableHashesFromFileProperty(['hash' => [
                'sha256' => 'abc',
                'unknown-algorithm' => 'def',
                // known to PHP, but not natively supported by the API
                'md5' => 'd41d8cd98f00b204e9800998ecf8427e',
                'blake3' => 'abc',
                0 => 'ghi',
            ]])
        );
    }

    public function testGetVerifiableHashesIgnoresNonStringValues(): void
    {
        $service = new FileHashService();

        $this->assertSame([], $service->getVerifiableHashesFromFileProperty(['hash' => ['sha256' => 123]]));
    }

    public function testGetVerifiableHashesReturnsEmptyArrayForMissingHash(): void
    {
        $service = new FileHashService();

        $this->assertSame([], $service->getVerifiableHashesFromFileProperty(null));
        $this->assertSame([], $service->getVerifiableHashesFromFileProperty('file'));
        $this->assertSame([], $service->getVerifiableHashesFromFileProperty([]));
        $this->assertSame([], $service->getVerifiableHashesFromFileProperty(['hash' => 'sha256']));
    }
}
