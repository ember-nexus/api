<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\MimeTypeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(MimeTypeService::class)]
class MimeTypeServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildMimeTypeService(
    ): MimeTypeService {
        return new MimeTypeService();
    }

    public function testGetMimeTypeFromResource(): void
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'Hello, World!');
        rewind($resource);

        $fileService = $this->buildMimeTypeService();
        $mimeType = $fileService->getMimeTypeFromResource($resource);

        self::assertSame('text/plain', $mimeType);

        fclose($resource);
    }

    public function testGetMimeTypeFromResourceReturnsFallbackForUnknownContent(): void
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, str_repeat("\x00", 16)); // null bytes → undetectable type
        rewind($resource);

        $fileService = $this->buildMimeTypeService();
        $mimeType = $fileService->getMimeTypeFromResource($resource);

        self::assertSame('application/octet-stream', $mimeType);

        fclose($resource);
    }
}
