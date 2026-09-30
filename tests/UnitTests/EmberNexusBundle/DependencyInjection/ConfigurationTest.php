<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EmberNexusBundle\DependencyInjection;

use EmberNexusBundle\DependencyInjection\Configuration;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

#[Small]
#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    public function testFileDefaults(): void
    {
        $config = $this->process([]);

        $this->assertSame([
            EmberNexusConfiguration::FILE_MAX_FILE_SIZE_IN_BYTES => 10 * 1024 * 1024 * 1024,
            EmberNexusConfiguration::FILE_UPLOAD_EXPIRES_IN_SECONDS_AFTER_FIRST_REQUEST => 3 * 3600,
            EmberNexusConfiguration::FILE_UPLOAD_CHUNK_DIGITS_LENGTH => 4,
            EmberNexusConfiguration::FILE_UPLOAD_MIN_CHUNK_SIZE_IN_BYTES => 5 * 1024 * 1024,
            EmberNexusConfiguration::FILE_UPLOAD_MAX_CHUNK_SIZE_IN_BYTES => 101 * 1024 * 1024,
            EmberNexusConfiguration::FILE_EXPIRED_UPLOAD_CAN_BE_DELETED_AFTER_EXPIRATION_IN_SECONDS => 3600,
            EmberNexusConfiguration::FILE_S3_STORAGE_BUCKET => 'api-storage',
            EmberNexusConfiguration::FILE_S3_UPLOAD_BUCKET => 'api-upload',
            EmberNexusConfiguration::FILE_S3_STORAGE_BUCKET_LEVELS => 3,
            EmberNexusConfiguration::FILE_S3_STORAGE_BUCKET_LEVEL_LENGTH => 2,
            EmberNexusConfiguration::FILE_S3_UPLOAD_BUCKET_LEVELS => 2,
            EmberNexusConfiguration::FILE_S3_UPLOAD_BUCKET_LEVEL_LENGTH => 2,
            EmberNexusConfiguration::FILE_S3_MAX_HOST_CONNECTIONS => 16,
        ], $config[EmberNexusConfiguration::FILE]);
    }

    public function testFileValuesCanBeOverwritten(): void
    {
        $config = $this->process([
            EmberNexusConfiguration::FILE => [
                EmberNexusConfiguration::FILE_MAX_FILE_SIZE_IN_BYTES => 1024,
                EmberNexusConfiguration::FILE_S3_STORAGE_BUCKET => 'my-storage',
                EmberNexusConfiguration::FILE_S3_UPLOAD_BUCKET => 'my-upload',
                EmberNexusConfiguration::FILE_S3_MAX_HOST_CONNECTIONS => 32,
            ],
        ]);

        $this->assertSame(1024, $config[EmberNexusConfiguration::FILE][EmberNexusConfiguration::FILE_MAX_FILE_SIZE_IN_BYTES]);
        $this->assertSame('my-storage', $config[EmberNexusConfiguration::FILE][EmberNexusConfiguration::FILE_S3_STORAGE_BUCKET]);
        $this->assertSame('my-upload', $config[EmberNexusConfiguration::FILE][EmberNexusConfiguration::FILE_S3_UPLOAD_BUCKET]);
        $this->assertSame(32, $config[EmberNexusConfiguration::FILE][EmberNexusConfiguration::FILE_S3_MAX_HOST_CONNECTIONS]);
        // untouched options keep their default
        $this->assertSame(4, $config[EmberNexusConfiguration::FILE][EmberNexusConfiguration::FILE_UPLOAD_CHUNK_DIGITS_LENGTH]);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function invalidFileValuesProvider(): array
    {
        return [
            'max file size of 0' => [EmberNexusConfiguration::FILE_MAX_FILE_SIZE_IN_BYTES, 0],
            'negative upload expiration' => [EmberNexusConfiguration::FILE_UPLOAD_EXPIRES_IN_SECONDS_AFTER_FIRST_REQUEST, -1],
            'chunk digits length of 0' => [EmberNexusConfiguration::FILE_UPLOAD_CHUNK_DIGITS_LENGTH, 0],
            'negative minimum chunk size' => [EmberNexusConfiguration::FILE_UPLOAD_MIN_CHUNK_SIZE_IN_BYTES, -1],
            'maximum chunk size of 0' => [EmberNexusConfiguration::FILE_UPLOAD_MAX_CHUNK_SIZE_IN_BYTES, 0],
            'negative grace period of expired uploads' => [EmberNexusConfiguration::FILE_EXPIRED_UPLOAD_CAN_BE_DELETED_AFTER_EXPIRATION_IN_SECONDS, -1],
            'storage bucket without levels' => [EmberNexusConfiguration::FILE_S3_STORAGE_BUCKET_LEVELS, 0],
            'storage bucket level length of 0' => [EmberNexusConfiguration::FILE_S3_STORAGE_BUCKET_LEVEL_LENGTH, 0],
            'upload bucket without levels' => [EmberNexusConfiguration::FILE_S3_UPLOAD_BUCKET_LEVELS, 0],
            'upload bucket level length of 0' => [EmberNexusConfiguration::FILE_S3_UPLOAD_BUCKET_LEVEL_LENGTH, 0],
            'max host connections of 0' => [EmberNexusConfiguration::FILE_S3_MAX_HOST_CONNECTIONS, 0],
            'max host connections of 1' => [EmberNexusConfiguration::FILE_S3_MAX_HOST_CONNECTIONS, 1],
        ];
    }

    #[DataProvider('invalidFileValuesProvider')]
    public function testInvalidFileValuesAreRejected(string $option, int $value): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf('ember_nexus.file.%s', $option));

        $this->process([EmberNexusConfiguration::FILE => [$option => $value]]);
    }

    public function testNonIntegerFileValueIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([EmberNexusConfiguration::FILE => [EmberNexusConfiguration::FILE_MAX_FILE_SIZE_IN_BYTES => 'big']]);
    }

    public function testUnknownFileOptionIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([EmberNexusConfiguration::FILE => ['unknownOption' => 1]]);
    }

    public function testPageSizeMinimumIsEnforced(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([EmberNexusConfiguration::PAGE_SIZE => [EmberNexusConfiguration::PAGE_SIZE_MIN => 0]]);
    }
}
