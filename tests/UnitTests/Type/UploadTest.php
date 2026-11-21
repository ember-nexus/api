<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\Upload;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Safe\DateTime;

#[Small]
#[CoversClass(Upload::class)]
class UploadTest extends TestCase
{
    public function testUpload(): void
    {
        $expires = new DateTime();
        $upload = new Upload(
            Uuid::fromString('02IiNr0G8viOVKkUqdrk3aYQ7GgQy6p9o7EKd'),
            1234,
            321,
            false,
            Uuid::fromString('02IiNr0G8viOVKkMTlulA45MFnt6AD4TdZG7p'),
            ['0123456789abcdef'],
            Uuid::fromString('02IiNr0G8viOVKkq3qvnfKXKmk5Xe2FhaCcE8'),
            'bin',
            $expires,
            null,
            true
        );

        $this->assertSame('02IiNr0G8viOVKkUqdrk3aYQ7GgQy6p9o7EKd', (string) $upload->getId());
        $this->assertSame(1234, $upload->getUploadLength());
        $this->assertSame(321, $upload->getUploadOffset());
        $this->assertSame(false, $upload->isUploadComplete());
        $this->assertSame('02IiNr0G8viOVKkMTlulA45MFnt6AD4TdZG7p', (string) $upload->getUploadTarget());
        $this->assertSame(1, $upload->getAlreadyUploadedChunks());
        $this->assertSame(['0123456789abcdef'], $upload->getChunkIds());
        $this->assertSame('02IiNr0G8viOVKkq3qvnfKXKmk5Xe2FhaCcE8', (string) $upload->getUploadOwner());
        $this->assertSame('bin', $upload->getExtension());
        $this->assertSame($expires, $upload->getExpires());
        $this->assertTrue($upload->targetHadFileAtCreation());
    }

    public function testTargetHadFileAtCreationDefaultsToFalse(): void
    {
        $upload = new Upload(
            Uuid::generate(),
            null,
            0,
            false,
            Uuid::generate(),
            [],
            Uuid::generate(),
            'bin',
            new DateTime()
        );

        $this->assertFalse($upload->targetHadFileAtCreation());
    }
}
