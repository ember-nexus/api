<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\FileSizeLimitService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(FileSizeLimitService::class)]
class FileSizeLimitServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildService(
        int $maxFileSizeInBytes,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
    ): FileSizeLimitService {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileMaxFileSizeInBytes()->willReturn($maxFileSizeInBytes);

        return new FileSizeLimitService(
            $emberNexusConfiguration->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal()
        );
    }

    public function testGetMaxFileSizeInBytesReturnsConfiguredValue(): void
    {
        $this->assertSame(1024, $this->buildService(1024)->getMaxFileSizeInBytes());
    }

    public function testExceedsMaxFileSizeIsFalseBelowLimit(): void
    {
        $this->assertFalse($this->buildService(1024)->exceedsMaxFileSize(1023));
    }

    public function testExceedsMaxFileSizeIsFalseExactlyAtLimit(): void
    {
        $this->assertFalse($this->buildService(1024)->exceedsMaxFileSize(1024));
    }

    public function testExceedsMaxFileSizeIsTrueAboveLimit(): void
    {
        $this->assertTrue($this->buildService(1024)->exceedsMaxFileSize(1025));
    }

    public function testExceedsMaxFileSizeIsFalseForEmptyFile(): void
    {
        $this->assertFalse($this->buildService(1024)->exceedsMaxFileSize(0));
    }

    public function testAssertWithinMaxFileSizePassesAtLimit(): void
    {
        $this->expectNotToPerformAssertions();
        $this->buildService(1024)->assertWithinMaxFileSize(1024);
    }

    public function testAssertWithinMaxFileSizeThrowsAboveLimit(): void
    {
        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory
            ->createFromDetail(Argument::containingString('at most 1024 bytes long, got 1025'))
            ->shouldBeCalledOnce()
            ->willReturn(new Client400BadContentException('bad-content'));

        $this->expectException(Client400BadContentException::class);
        $this->buildService(1024, $client400BadContentExceptionFactory->reveal())->assertWithinMaxFileSize(1025);
    }
}
