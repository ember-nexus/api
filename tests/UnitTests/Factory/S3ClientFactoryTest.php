<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory;

use App\Factory\S3ClientFactory;
use AsyncAws\S3\S3Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(S3ClientFactory::class)]
class S3ClientFactoryTest extends TestCase
{
    public function testCreateS3ClientReturnsClient(): void
    {
        $factory = new S3ClientFactory('http://s3.example.test:9000', 'access-key', 'secret-key');

        $this->assertInstanceOf(S3Client::class, $factory->createS3Client());
    }
}
