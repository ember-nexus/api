<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\UploadBodyLimitService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(UploadBodyLimitService::class)]
class UploadBodyLimitServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildService(): UploadBodyLimitService
    {
        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileUploadMaxChunkSizeInBytes()->willReturn(10);
        $factory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $factory->createFromDetail(Argument::any())->will(fn ($args) => new Client400BadContentException('type', detail: $args[0]));

        return new UploadBodyLimitService($configuration->reveal(), $factory->reveal());
    }

    /**
     * @return resource
     */
    private function resource(string $content)
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }

    public function testDeclaredLengthAtOrBelowLimitOrMissingIsAccepted(): void
    {
        $service = $this->buildService();
        $service->assertDeclaredLengthWithinLimit(null);
        $service->assertDeclaredLengthWithinLimit(10);
        $this->expectNotToPerformAssertions();
    }

    public function testDeclaredLengthAboveLimitIsRejected(): void
    {
        try {
            $this->buildService()->assertDeclaredLengthWithinLimit(11);
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString('at most 10 bytes long, got 11', $exception->getDetail());

            return;
        }
        $this->fail('Expected rejection.');
    }

    public function testBoundContentReturnsRewoundCopyWithinLimit(): void
    {
        $bounded = $this->buildService()->boundContent($this->resource('0123456789'), null);

        $this->assertSame('0123456789', stream_get_contents($bounded));
    }

    public function testBoundContentRejectsOversizedBodyWithoutContentLength(): void
    {
        $this->expectException(Client400BadContentException::class);
        $this->buildService()->boundContent($this->resource('01234567890'), null);
    }

    public function testBoundContentRejectsDeclaredOversizeBeforeReading(): void
    {
        $resource = $this->resource('abc');

        try {
            $this->buildService()->boundContent($resource, 11);
            $this->fail('Expected rejection.');
        } catch (Client400BadContentException) {
            $this->assertSame('abc', stream_get_contents($resource));
        }
    }

    public function testBoundContentAcceptsBodyMatchingDeclaredLength(): void
    {
        $bounded = $this->buildService()->boundContent($this->resource('0123456789'), 10);

        $this->assertSame('0123456789', stream_get_contents($bounded));
    }

    public function testBoundContentRejectsBodyShorterThanDeclaredLength(): void
    {
        // e.g. a body which was cut off by the request timeout of the web server
        $this->expectException(Client400BadContentException::class);
        $this->buildService()->boundContent($this->resource('01234'), 10);
    }
}
