<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Service\FileSizeLimitService;
use App\Service\UploadChunkValidator;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

#[Small]
#[CoversClass(UploadChunkValidator::class)]
class UploadChunkValidatorTest extends TestCase
{
    use ProphecyTrait;

    private const int MIN_CHUNK_SIZE = 100;
    private const int MAX_CHUNK_SIZE = 1000;

    private ObjectProphecy $fileSizeLimitService;

    private function createValidator(): UploadChunkValidator
    {
        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileUploadMinChunkSizeInBytes()->willReturn(self::MIN_CHUNK_SIZE);
        $configuration->getFileUploadMaxChunkSizeInBytes()->willReturn(self::MAX_CHUNK_SIZE);

        $this->fileSizeLimitService = $this->prophesize(FileSizeLimitService::class);

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::any())->will(
            fn ($args) => new Client400BadContentException('type', detail: $args[0])
        );
        $client409ConflictExceptionFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409ConflictExceptionFactory->createFromDetail(Argument::cetera())->will(
            fn ($args) => new Client409ConflictException('type', detail: $args[0])
        );

        return new UploadChunkValidator(
            $configuration->reveal(),
            $this->fileSizeLimitService->reveal(),
            $client400BadContentExceptionFactory->reveal(),
            $client409ConflictExceptionFactory->reveal(),
        );
    }

    /**
     * @return array<string, array{int, bool, int, ?int}>
     */
    public static function validChunkProvider(): array
    {
        return [
            'intermediate chunk at minimum' => [100, false, 0, null],
            'intermediate chunk at maximum' => [1000, false, 0, null],
            'final chunk below minimum' => [1, true, 500, null],
            'empty final chunk' => [0, true, 500, null],
            'final chunk at maximum' => [1000, true, 0, null],
            'intermediate chunk within declared length' => [100, false, 100, 300],
            'intermediate chunk exactly reaching declared length' => [100, false, 200, 300],
            'final chunk matching declared length' => [50, true, 250, 300],
        ];
    }

    #[DataProvider('validChunkProvider')]
    public function testValidChunksAreAccepted(int $chunkLength, bool $isFinalChunk, int $uploadOffset, ?int $uploadLength): void
    {
        $validator = $this->createValidator();
        $this->fileSizeLimitService->assertWithinMaxFileSize($uploadOffset + $chunkLength)->shouldBeCalledOnce();

        $validator->assertValidChunk($chunkLength, $isFinalChunk, $uploadOffset, $uploadLength);
        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{int, bool, int, ?int, class-string, string}>
     */
    public static function invalidChunkProvider(): array
    {
        return [
            'intermediate chunk below minimum' => [99, false, 0, null, Client400BadContentException::class, 'at least 100 bytes long, got 99'],
            'empty intermediate chunk' => [0, false, 0, null, Client400BadContentException::class, 'at least 100 bytes long, got 0'],
            'intermediate chunk above maximum' => [1001, false, 0, null, Client400BadContentException::class, 'at most 1000 bytes long, got 1001'],
            'final chunk above maximum' => [1001, true, 0, null, Client400BadContentException::class, 'at most 1000 bytes long, got 1001'],
            'chunk exceeding declared length' => [200, false, 200, 300, Client409ConflictException::class, 'exceeds defined upload length'],
            'final chunk exceeding declared length' => [50, true, 260, 300, Client409ConflictException::class, 'exceeds defined upload length'],
            'final chunk below declared length' => [50, true, 200, 300, Client409ConflictException::class, 'has 250 bytes, but the defined upload length is 300 bytes'],
        ];
    }

    /**
     * @param class-string<Throwable> $exceptionClass
     */
    #[DataProvider('invalidChunkProvider')]
    public function testInvalidChunksAreRejected(int $chunkLength, bool $isFinalChunk, int $uploadOffset, ?int $uploadLength, string $exceptionClass, string $messagePart): void
    {
        $validator = $this->createValidator();
        $this->fileSizeLimitService->assertWithinMaxFileSize(Argument::any())->will(function () {});

        try {
            $validator->assertValidChunk($chunkLength, $isFinalChunk, $uploadOffset, $uploadLength);
            $this->fail('Expected exception was not thrown.');
        } catch (Client400BadContentException|Client409ConflictException $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertStringContainsString($messagePart, $exception->getDetail());
        }
    }

    public function testRunningTotalAboveMaxFileSizeIsRejected(): void
    {
        $validator = $this->createValidator();
        $this->fileSizeLimitService->assertWithinMaxFileSize(1500)->willThrow(new Client400BadContentException('type', detail: 'too big'));

        $this->expectException(Client400BadContentException::class);
        $validator->assertChunkSize(500, false, 1000);
    }

    public function testChunkSizeCheckIgnoresDeclaredLength(): void
    {
        $validator = $this->createValidator();
        $this->fileSizeLimitService->assertWithinMaxFileSize(Argument::any())->will(function () {});

        // only assertWithinDeclaredLength() knows about the declared length
        $validator->assertChunkSize(500, false, 0);
        $this->addToAssertionCount(1);
    }

    public function testDeclaredLengthIsOptional(): void
    {
        $validator = $this->createValidator();

        $validator->assertWithinDeclaredLength(999999, true, 999999, null);
        $this->addToAssertionCount(1);
    }

    public function testDeclaredLengthCheckIgnoresChunkSizeLimits(): void
    {
        $validator = $this->createValidator();

        // 50 bytes intermediate chunk is below the minimum, which is not the concern of this check
        $validator->assertWithinDeclaredLength(50, false, 0, 300);
        $this->addToAssertionCount(1);
    }
}
