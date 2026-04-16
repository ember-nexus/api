<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\Upload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;

#[Small]
#[CoversClass(Upload::class)]
class UploadTest extends TestCase
{
    public function testUpload(): void
    {
        $expires = new DateTime();
        $upload = new Upload(
            Uuid::fromString('93257437-6f77-4b2d-87a4-3eea785d13ae'),
            1234,
            321,
            false,
            Uuid::fromString('81fbe257-08e0-465b-aa32-8b002a7517c5'),
            1,
            Uuid::fromString('beb42bb3-3b3d-40d2-895b-e54ffb7816dd'),
            'bin',
            $expires
        );

        $this->assertSame('93257437-6f77-4b2d-87a4-3eea785d13ae', (string) $upload->getId());
        $this->assertSame(1234, $upload->getUploadLength());
        $this->assertSame(321, $upload->getUploadOffset());
        $this->assertSame(false, $upload->isUploadComplete());
        $this->assertSame('81fbe257-08e0-465b-aa32-8b002a7517c5', (string) $upload->getUploadTarget());
        $this->assertSame(1, $upload->getAlreadyUploadedChunks());
        $this->assertSame('beb42bb3-3b3d-40d2-895b-e54ffb7816dd', (string) $upload->getUploadOwner());
        $this->assertSame('bin', $upload->getExtension());
        $this->assertSame($expires, $upload->getExpires());
    }
}
