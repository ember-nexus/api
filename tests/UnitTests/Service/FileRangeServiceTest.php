<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client416RangeNotSatisfiableException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client416RangeNotSatisfiableExceptionFactory;
use App\Service\FileRangeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(FileRangeService::class)]
class FileRangeServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildService(): FileRangeService
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('exception-detail', [
            'code' => '400',
            'name' => 'bad-content',
        ], UrlGeneratorInterface::ABSOLUTE_URL)->willReturn('http://localhost/error/400/bad-content');
        $urlGenerator->generate('exception-detail', [
            'code' => '416',
            'name' => 'range-not-satisfiable',
        ], UrlGeneratorInterface::ABSOLUTE_URL)->willReturn('http://localhost/error/416/range-not-satisfiable');

        return new FileRangeService(
            new Client400BadContentExceptionFactory($urlGenerator->reveal()),
            new Client416RangeNotSatisfiableExceptionFactory($urlGenerator->reveal())
        );
    }

    public function testThrowsBadContentForMalformedRangeHeader(): void
    {
        $service = $this->buildService();

        $this->expectException(Client400BadContentException::class);
        $service->parseRangeHeader('not-a-range', 100);
    }

    public function testThrowsBadContentForMultipleRanges(): void
    {
        $service = $this->buildService();

        $this->expectException(Client400BadContentException::class);
        $service->parseRangeHeader('bytes=0-10,20-30', 100);
    }

    public function testThrowsBadContentForEmptyRange(): void
    {
        $service = $this->buildService();

        $this->expectException(Client400BadContentException::class);
        $service->parseRangeHeader('bytes=-', 100);
    }

    public function testParsesExplicitRange(): void
    {
        $service = $this->buildService();

        $range = $service->parseRangeHeader('bytes=0-99', 1000);

        $this->assertSame(0, $range->getStart());
        $this->assertSame(99, $range->getEnd());
        $this->assertSame(1000, $range->getTotalLength());
        $this->assertSame(100, $range->getLength());
    }

    public function testParsesOpenEndedRange(): void
    {
        $service = $this->buildService();

        $range = $service->parseRangeHeader('bytes=900-', 1000);

        $this->assertSame(900, $range->getStart());
        $this->assertSame(999, $range->getEnd());
        $this->assertSame(100, $range->getLength());
    }

    public function testParsesSuffixRange(): void
    {
        $service = $this->buildService();

        $range = $service->parseRangeHeader('bytes=-100', 1000);

        $this->assertSame(900, $range->getStart());
        $this->assertSame(999, $range->getEnd());
        $this->assertSame(100, $range->getLength());
    }

    public function testSuffixRangeLongerThanFileIsClampedToFullFile(): void
    {
        $service = $this->buildService();

        $range = $service->parseRangeHeader('bytes=-10000', 1000);

        $this->assertSame(0, $range->getStart());
        $this->assertSame(999, $range->getEnd());
        $this->assertSame(1000, $range->getLength());
    }

    public function testEndIsClampedToLastByteOfFile(): void
    {
        $service = $this->buildService();

        $range = $service->parseRangeHeader('bytes=990-999999', 1000);

        $this->assertSame(990, $range->getStart());
        $this->assertSame(999, $range->getEnd());
    }

    public function testThrowsIfStartIsBeyondEndOfFile(): void
    {
        $service = $this->buildService();

        $this->expectException(Client416RangeNotSatisfiableException::class);
        $service->parseRangeHeader('bytes=1000-', 1000);
    }

    public function testThrowsIfFileIsEmpty(): void
    {
        $service = $this->buildService();

        $this->expectException(Client416RangeNotSatisfiableException::class);
        $service->parseRangeHeader('bytes=0-10', 0);
    }

    public function testThrowsForZeroLengthSuffixRange(): void
    {
        $service = $this->buildService();

        $this->expectException(Client416RangeNotSatisfiableException::class);
        $service->parseRangeHeader('bytes=-0', 1000);
    }
}
