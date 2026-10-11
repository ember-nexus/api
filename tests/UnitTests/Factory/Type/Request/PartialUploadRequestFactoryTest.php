<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\Request;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client408RequestTimeoutExceptionFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Service\HeaderParseService;
use App\Service\UploadBodyLimitService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(PartialUploadRequestFactory::class)]
class PartialUploadRequestFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function buildLimitService(int $maxChunkSize = 100000): UploadBodyLimitService
    {
        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileUploadMaxChunkSizeInBytes()->willReturn($maxChunkSize);

        return new UploadBodyLimitService($configuration->reveal(), $this->buildBadContentFactory(), new Client408RequestTimeoutExceptionFactory($this->createStub(UrlGeneratorInterface::class)));
    }

    private function buildBadContentFactory(): Client400BadContentExceptionFactory
    {
        $factory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $factory->createFromDetail(Argument::any())->will(fn ($args) => new Client400BadContentException('type', detail: $args[0]));

        return $factory->reveal();
    }

    public function testMissingUploadCompleteHeaderIsRejected(): void
    {
        $headers = $this->prophesize(HeaderBag::class)->reveal();
        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->shouldNotBeCalled();
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);
        $headerParseService->getContentTypeFromHeaders(Argument::cetera())->willReturn('application/partial-upload');
        $headerParseService->getUploadOffsetFromHeaders(Argument::is($headers))->willReturn(0);
        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->willReturn(null);

        $factory = new PartialUploadRequestFactory($headerParseService->reveal(), $this->buildLimitService(), $this->buildBadContentFactory());

        try {
            $factory->createPartialUploadRequestFromRequest($request);
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString('Upload-Complete', $exception->getDetail());

            return;
        }
        $this->fail('Expected rejection.');
    }

    /**
     * @return array<string, array{int|null, string}>
     */
    public static function oversizedBodyProvider(): array
    {
        return [
            'declared length above cap' => [11, 'short'],
            'missing content length, actual body above cap' => [null, 'this body is longer than ten bytes'],
        ];
    }

    #[DataProvider('oversizedBodyProvider')]
    public function testBodyAboveChunkSizeCapIsRejected(?int $declaredLength, string $body): void
    {
        $headers = $this->prophesize(HeaderBag::class)->reveal();
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $body);
        rewind($resource);
        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->willReturn($resource);
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);
        $headerParseService->getContentTypeFromHeaders(Argument::cetera())->willReturn('application/partial-upload');
        $headerParseService->getUploadOffsetFromHeaders(Argument::is($headers))->willReturn(0);
        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->willReturn(true);
        $headerParseService->getContentLengthFromHeaders(Argument::is($headers))->willReturn($declaredLength);

        $factory = new PartialUploadRequestFactory($headerParseService->reveal(), $this->buildLimitService(10), $this->buildBadContentFactory());

        try {
            $factory->createPartialUploadRequestFromRequest($request);
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString('at most 10 bytes', $exception->getDetail());

            return;
        }
        $this->fail('Expected rejection.');
    }

    public function testCreatePartialUploadRequestFromRequest(): void
    {
        $headers = $this->prophesize(HeaderBag::class)->reveal();
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'some content');
        rewind($resource);

        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->shouldBeCalledOnce()->willReturn($resource);
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);

        $headerParseService->getContentTypeFromHeaders(Argument::is($headers), Argument::is('application/partial-upload'))->shouldBeCalledOnce()->willReturn('application/partial-upload');
        $headerParseService->getUploadOffsetFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(1234);
        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(false);
        $headerParseService->getContentLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(12);

        $partialUploadRequestFactory = new PartialUploadRequestFactory($headerParseService->reveal(), $this->buildLimitService(), $this->buildBadContentFactory());

        $partialUploadRequest = $partialUploadRequestFactory->createPartialUploadRequestFromRequest($request);

        $this->assertSame('some content', stream_get_contents($partialUploadRequest->getContent()));
        $this->assertSame('application/partial-upload', $partialUploadRequest->getContentType());
        $this->assertSame(1234, $partialUploadRequest->getUploadOffset());
        $this->assertFalse($partialUploadRequest->isUploadComplete());
        $this->assertSame(12, $partialUploadRequest->getContentLength());
    }
}
