<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\Request;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\Request\ResumableUploadRequestFactory;
use App\Service\HeaderParseService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

#[Small]
#[CoversClass(ResumableUploadRequestFactory::class)]
class ResumableUploadRequestFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function buildResumableUploadRequestFactory(
        ?HeaderParseService $headerParseService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
    ): ResumableUploadRequestFactory {
        return new ResumableUploadRequestFactory(
            $headerParseService ?? $this->prophesize(HeaderParseService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
        );
    }

    public function testCreateResumableUploadRequestFromRequest(): void
    {
        $elementId = Uuid::fromString('c01ab4f8-ab3c-4372-9aca-e919f501ad5c');

        $headers = $this->prophesize(HeaderBag::class)->reveal();

        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->shouldBeCalledOnce()->willReturn('some content');
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);

        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(false);
        $headerParseService->getUploadLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(654321);
        $headerParseService->getContentLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(4321);
        $headerParseService->getExtensionFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn('png');

        $resumableUploadRequestFactory = $this->buildResumableUploadRequestFactory(
            headerParseService: $headerParseService->reveal()
        );

        $resumableUploadRequest = $resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);

        $this->assertSame($elementId, $resumableUploadRequest->getElementId());
        $this->assertSame('some content', $resumableUploadRequest->getContent());
        $this->assertFalse($resumableUploadRequest->isUploadComplete());
        $this->assertSame(654321, $resumableUploadRequest->getUploadLength());
        $this->assertSame(4321, $resumableUploadRequest->getContentLength());
        $this->assertSame('png', $resumableUploadRequest->getExtension());
    }

    public function testCreateResumableUploadRequestFromRequestThrowsOnLengthMismatchForCompleteUploads(): void
    {
        $elementId = Uuid::fromString('c01ab4f8-ab3c-4372-9aca-e919f501ad5c');

        $headers = $this->prophesize(HeaderBag::class)->reveal();

        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->shouldBeCalledOnce()->willReturn('some content');
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);

        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(true);
        $headerParseService->getUploadLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(654321);
        $headerParseService->getContentLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(4321);
        $headerParseService->getExtensionFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn('png');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Inconsistent length values provided in headers 'Content-Length' and 'Upload-Length'."))->shouldBeCalledOnce()->willReturn($exception);

        $resumableUploadRequestFactory = $this->buildResumableUploadRequestFactory(
            headerParseService: $headerParseService->reveal(),
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);
    }

    public function testCreateResumableUploadRequestFromRequestDoesNotThrowWhenUploadLengthIsNull(): void
    {
        $elementId = Uuid::fromString('c01ab4f8-ab3c-4372-9aca-e919f501ad5c');

        $headers = $this->prophesize(HeaderBag::class)->reveal();

        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->shouldBeCalledOnce()->willReturn('some content');
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);

        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(true);
        $headerParseService->getUploadLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(null);
        $headerParseService->getContentLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(4321);
        $headerParseService->getExtensionFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn('png');

        $resumableUploadRequestFactory = $this->buildResumableUploadRequestFactory(
            headerParseService: $headerParseService->reveal(),
        );

        $resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);
    }

    public function testCreateResumableUploadRequestFromRequestDoesNotThrowWhenContentLengthIsNull(): void
    {
        $elementId = Uuid::fromString('c01ab4f8-ab3c-4372-9aca-e919f501ad5c');

        $headers = $this->prophesize(HeaderBag::class)->reveal();

        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->shouldBeCalledOnce()->willReturn('some content');
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);

        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(true);
        $headerParseService->getUploadLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(654321);
        $headerParseService->getContentLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(null);
        $headerParseService->getExtensionFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn('png');

        $resumableUploadRequestFactory = $this->buildResumableUploadRequestFactory(
            headerParseService: $headerParseService->reveal(),
        );

        $resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);
    }
}
