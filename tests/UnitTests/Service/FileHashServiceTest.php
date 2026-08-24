<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\FileHashService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(FileHashService::class)]
class FileHashServiceTest extends TestCase
{
    public function testCalculatesSha256HashOfResourceContent(): void
    {
        $content = 'some deterministic content used to verify the calculated hash';
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, $content);
        rewind($resource);

        $service = new FileHashService();
        $hash = $service->calculateHashFromResource($resource);

        $this->assertSame(hash('sha256', $content), $hash);
        $this->assertSame(64, strlen($hash));
    }

    public function testCalculatesCorrectHashForEmptyResource(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');

        $service = new FileHashService();
        $hash = $service->calculateHashFromResource($resource);

        $this->assertSame(hash('sha256', ''), $hash);
    }
}
