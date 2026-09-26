<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\FileService;
use App\Service\PropertyParseService;
use DateTimeImmutable as NativeDateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Safe\DateTimeImmutable;

#[Small]
#[CoversClass(PropertyParseService::class)]
class PropertyParseServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildService(): PropertyParseService
    {
        $factory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $factory
            ->createFromDetail(Argument::type('string'))
            ->will(fn (array $args) => new Client400BadContentException('bad-content', detail: $args[0]));

        return new PropertyParseService($factory->reveal());
    }

    /**
     * @param callable(PropertyParseService): mixed $call
     */
    private function assertBadContent(callable $call, string $expectedDetail): void
    {
        try {
            $call($this->buildService());
        } catch (Client400BadContentException $exception) {
            $this->assertSame($expectedDetail, $exception->getDetail());

            return;
        }
        $this->fail('Expected Client400BadContentException.');
    }

    public function testUploadLengthIsNullWhenAbsent(): void
    {
        $this->assertNull($this->buildService()->getUploadLengthFromProperties([]));
    }

    public function testUploadLengthIsReturned(): void
    {
        $this->assertSame(42, $this->buildService()->getUploadLengthFromProperties(['uploadLength' => 42]));
    }

    #[DataProvider('nonIntProvider')]
    public function testUploadLengthMustBeInt(mixed $value): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadLengthFromProperties(['uploadLength' => $value]),
            'Upload expects property uploadLength to be either int or to be absent.'
        );
    }

    public function testUploadOffsetMustBePresent(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadOffsetFromProperties([]),
            'Upload expects property uploadOffset to be present.'
        );
    }

    #[DataProvider('nonIntProvider')]
    public function testUploadOffsetMustBeInt(mixed $value): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadOffsetFromProperties(['uploadOffset' => $value]),
            'Upload expects property uploadOffset to be int.'
        );
    }

    public function testUploadOffsetMustNotBeNegative(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadOffsetFromProperties(['uploadOffset' => -1]),
            'Upload expects property uploadOffset to be positive int.'
        );
    }

    public function testUploadOffsetIsReturned(): void
    {
        $this->assertSame(0, $this->buildService()->getUploadOffsetFromProperties(['uploadOffset' => 0]));
        $this->assertSame(7, $this->buildService()->getUploadOffsetFromProperties(['uploadOffset' => 7]));
    }

    public function testIsUploadCompleteDefaultsToFalse(): void
    {
        $this->assertFalse($this->buildService()->getIsUploadCompleteFromProperties([]));
    }

    public function testIsUploadCompleteIsReturned(): void
    {
        $this->assertTrue($this->buildService()->getIsUploadCompleteFromProperties(['uploadComplete' => true]));
        $this->assertFalse($this->buildService()->getIsUploadCompleteFromProperties(['uploadComplete' => false]));
    }

    public function testIsUploadCompleteMustBeBool(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getIsUploadCompleteFromProperties(['uploadComplete' => 1]),
            'Upload expects property uploadComplete to be bool.'
        );
    }

    public function testUploadTargetMustBePresent(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadTargetFromProperties([]),
            'Upload expects property uploadTarget to be present.'
        );
    }

    public function testUploadTargetMustBeUuid(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadTargetFromProperties(['uploadTarget' => 12]),
            'Upload expects property uploadTarget to be a uuid, got int.'
        );
    }

    public function testUploadTargetIsParsedFromString(): void
    {
        $uuid = Uuid::uuid4();
        $this->assertTrue($uuid->equals($this->buildService()->getUploadTargetFromProperties(['uploadTarget' => $uuid->toString()])));
    }

    public function testUploadTargetIsReturnedFromUuid(): void
    {
        $uuid = Uuid::uuid4();
        $this->assertSame($uuid, $this->buildService()->getUploadTargetFromProperties(['uploadTarget' => $uuid]));
    }

    public function testChunkIdsDefaultToEmptyList(): void
    {
        $this->assertSame([], $this->buildService()->getChunkIdsFromProperties([]));
    }

    public function testChunkIdsAreReturned(): void
    {
        $this->assertSame(['a', 'b'], $this->buildService()->getChunkIdsFromProperties(['chunkIds' => ['a', 'b']]));
    }

    public function testChunkIdsMustBeList(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getChunkIdsFromProperties(['chunkIds' => 'a']),
            'Upload expects property chunkIds to be a list of strings.'
        );
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getChunkIdsFromProperties(['chunkIds' => ['x' => 'a']]),
            'Upload expects property chunkIds to be a list of strings.'
        );
    }

    public function testChunkIdsMustContainStrings(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getChunkIdsFromProperties(['chunkIds' => ['a', 3]]),
            'Upload expects property chunkIds to be a list of strings.'
        );
    }

    public function testUploadOwnerMustBePresent(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadOwnerFromProperties([]),
            'Upload expects property uploadOwner to be present.'
        );
    }

    public function testUploadOwnerMustBeUuid(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getUploadOwnerFromProperties(['uploadOwner' => null]),
            'Upload expects property uploadOwner to be a uuid, got null.'
        );
    }

    public function testUploadOwnerIsParsedFromString(): void
    {
        $uuid = Uuid::uuid4();
        $this->assertTrue($uuid->equals($this->buildService()->getUploadOwnerFromProperties(['uploadOwner' => $uuid->toString()])));
    }

    public function testUploadOwnerIsReturnedFromUuid(): void
    {
        $uuid = Uuid::uuid4();
        $this->assertSame($uuid, $this->buildService()->getUploadOwnerFromProperties(['uploadOwner' => $uuid]));
    }

    public function testExtensionDefaultsToDefaultExtension(): void
    {
        $this->assertSame(FileService::DEFAULT_EXTENSION, $this->buildService()->getExtensionFromProperties([]));
    }

    public function testExtensionIsReturned(): void
    {
        $this->assertSame('png', $this->buildService()->getExtensionFromProperties(['extension' => 'png']));
    }

    public function testExtensionMustBeString(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getExtensionFromProperties(['extension' => 5]),
            'Upload expects property extension to be string.'
        );
    }

    public function testHashStateIsNullWhenAbsent(): void
    {
        $this->assertNull($this->buildService()->getHashStateFromProperties([]));
    }

    public function testHashStateIsReturned(): void
    {
        $this->assertSame('abc', $this->buildService()->getHashStateFromProperties(['hashState' => 'abc']));
    }

    public function testHashStateMustBeString(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getHashStateFromProperties(['hashState' => null]),
            'Upload expects property hashState to be either string or to be absent.'
        );
    }

    public function testExpiresMustBePresent(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getExpiresFromProperties([]),
            'Upload expects property expires to be present.'
        );
    }

    public function testExpiresMustBeDateTime(): void
    {
        $this->assertBadContent(
            fn (PropertyParseService $service) => $service->getExpiresFromProperties(['expires' => '2020-01-01']),
            'Upload expects property expires to be a DateTime, got string.'
        );
    }

    public function testExpiresIsReturnedFromDateTime(): void
    {
        $expires = new DateTime('2020-01-01T00:00:00+00:00');
        $this->assertSame($expires, $this->buildService()->getExpiresFromProperties(['expires' => $expires]));
    }

    public function testExpiresIsConvertedFromImmutableVariants(): void
    {
        foreach ([new DateTimeImmutable('2020-01-01T00:00:00+00:00'), new NativeDateTimeImmutable('2020-01-01T00:00:00+00:00')] as $immutable) {
            $result = $this->buildService()->getExpiresFromProperties(['expires' => $immutable]);
            $this->assertInstanceOf(DateTime::class, $result);
            $this->assertSame($immutable->getTimestamp(), $result->getTimestamp());
        }
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function nonIntProvider(): array
    {
        return [
            'string' => ['5'],
            'float' => [1.5],
            'bool' => [true],
            'null' => [null],
            'array' => [[1]],
        ];
    }
}
