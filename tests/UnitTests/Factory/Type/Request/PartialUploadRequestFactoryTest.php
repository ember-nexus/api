<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\Request;

use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Service\HeaderParseService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

#[Small]
#[CoversClass(PartialUploadRequestFactory::class)]
class PartialUploadRequestFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreatePartialUploadRequestFromRequest(): void
    {
        $headers = $this->prophesize(HeaderBag::class)->reveal();

        $request = $this->prophesize(Request::class);
        $request->getContent(Argument::is(true))->shouldBeCalledOnce()->willReturn('some content');
        $request = $request->reveal();
        $request->headers = $headers;

        $headerParseService = $this->prophesize(HeaderParseService::class);

        $headerParseService->getContentTypeFromHeaders(Argument::is($headers), Argument::is('application/partial-upload'))->shouldBeCalledOnce()->willReturn('application/partial-upload');
        $headerParseService->getUploadOffsetFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(1234);
        $headerParseService->isUploadCompleteFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(false);
        $headerParseService->getContentLengthFromHeaders(Argument::is($headers))->shouldBeCalledOnce()->willReturn(4321);

        $partialUploadRequestFactory = new PartialUploadRequestFactory($headerParseService->reveal());

        $partialUploadRequest = $partialUploadRequestFactory->createPartialUploadRequestFromRequest($request);

        $this->assertSame('some content', $partialUploadRequest->getContent());
        $this->assertSame('application/partial-upload', $partialUploadRequest->getContentType());
        $this->assertSame(1234, $partialUploadRequest->getUploadOffset());
        $this->assertFalse($partialUploadRequest->isUploadComplete());
        $this->assertSame(4321, $partialUploadRequest->getContentLength());
    }
}
