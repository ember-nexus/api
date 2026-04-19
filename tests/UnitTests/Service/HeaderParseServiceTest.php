<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\FileService;
use App\Service\HeaderParseService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\HeaderBag;

#[Small]
#[CoversClass(HeaderParseService::class)]
class HeaderParseServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildHeaderParseService(
        ?FileService $fileService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
    ): HeaderParseService {
        return new HeaderParseService(
            $fileService ?? $this->prophesize(FileService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
        );
    }

    public static function contentTypeProvider(): array
    {
        return [
            ['text/html', 'text/html'],
            ['Text/HTML', 'text/html'],
            ['TEXT/HTML', 'text/html'],
            ['Text/HTML;Charset="utf-8"', 'text/html'],
            ['text/html ; charset="utf-8"', 'text/html'],
            ['application/partial-upload', 'application/partial-upload'],
        ];
    }

    #[DataProvider('contentTypeProvider')]
    public function testGetContentTypeFromHeaders(string $headerValue, string $contentType): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Type'))->shouldBeCalledOnce()->willReturn($headerValue);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->getContentTypeFromHeaders($headerBag->reveal());
        $this->assertSame($contentType, $result);
    }

    public static function contentTypeWithExpectedContentTypeProvider(): array
    {
        return [
            ['text/html', 'text/html', 'text/html'],
            ['Text/Html', 'text/HTML', 'text/html'],
            ['TEXT/HTML', 'TEXT/HTML', 'text/html'],
        ];
    }

    #[DataProvider('contentTypeWithExpectedContentTypeProvider')]
    public function testGetContentTypeFromHeadersWorksWhenContentTypeMatchesExpectedContentType(string $headerValue, string $expectedContentType, string $contentType): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Type'))->shouldBeCalledOnce()->willReturn($headerValue);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->getContentTypeFromHeaders($headerBag->reveal(), $expectedContentType);
        $this->assertSame($contentType, $result);
    }

    public function testGetContentTypeFromHeadersThrowsWhenContentTypeDoesNotMatchExpectedContentType(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Type'))->shouldBeCalledOnce()->willReturn('text/html');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Expected content type 'text/plain', got 'text/html'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getContentTypeFromHeaders($headerBag->reveal(), 'text/plain');
    }

    public function testGetContentTypeFromHeadersThrowsWhenHeaderIsMissing(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Type'))->shouldBeCalledOnce()->willReturn(null);

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Endpoint requires the header 'content-type' to be present."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getContentTypeFromHeaders($headerBag->reveal());
    }

    public function testGetContentTypeFromHeadersThrowsWhenContentTypeIsEmptyString(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Type'))->shouldBeCalledOnce()->willReturn('');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Content-Type' must contain a valid MIME type, got an empty value."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getContentTypeFromHeaders($headerBag->reveal());
    }

    public static function uploadOffset(): array
    {
        return [
            ['0', 0],
            ['1', 1],
            ['1234', 1234],
            ['6543', 6543],
        ];
    }

    #[DataProvider('uploadOffset')]
    public function testGetUploadOffsetFromHeaders(string $headerValue, int $uploadOffset): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Offset'))->shouldBeCalledOnce()->willReturn($headerValue);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->getUploadOffsetFromHeaders($headerBag->reveal());
        $this->assertSame($uploadOffset, $result);
    }

    public function testGetUploadOffsetFromHeadersThrowsWhenOffsetIsMissing(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Offset'))->shouldBeCalledOnce()->willReturn(null);

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Upload-Offset' is required."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getUploadOffsetFromHeaders($headerBag->reveal());
    }

    public function testGetUploadOffsetFromHeadersThrowsWhenOffsetIsNotNumeric(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Offset'))->shouldBeCalledOnce()->willReturn('123abc');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Upload-Offset' requires a non-negative integer as its value, got '123abc'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getUploadOffsetFromHeaders($headerBag->reveal());
    }

    public function testGetUploadOffsetFromHeadersThrowsWhenOffsetIsNegative(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Offset'))->shouldBeCalledOnce()->willReturn('-4321');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Upload-Offset' requires a non-negative integer as its value, got '-4321'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getUploadOffsetFromHeaders($headerBag->reveal());
    }

    public static function isUploadCompleteProvider(): array
    {
        return [
            ['?0', false],
            ['?1', true],
            [null, null],
        ];
    }

    #[DataProvider('isUploadCompleteProvider')]
    public function testIsUploadCompleteFromHeaders(?string $headerValue, ?bool $contentType): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Complete'))->shouldBeCalledOnce()->willReturn($headerValue);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->isUploadCompleteFromHeaders($headerBag->reveal());
        $this->assertSame($contentType, $result);
    }

    public function testIsUploadCompleteFromHeadersThrowsWhenValueIsInvalid(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Complete'))->shouldBeCalledOnce()->willReturn('iDoNotExist');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Upload-Complete' must contain a boolean value, either '?0' or '?1', got 'iDoNotExist'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->isUploadCompleteFromHeaders($headerBag->reveal());
    }

    public static function contentLength(): array
    {
        return [
            ['0', 0],
            ['1', 1],
            ['1234', 1234],
            ['6543', 6543],
        ];
    }

    #[DataProvider('contentLength')]
    public function testGetContentLengthFromHeaders(string $headerValue, int $contentLength): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Length'))->shouldBeCalledOnce()->willReturn($headerValue);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->getContentLengthFromHeaders($headerBag->reveal());
        $this->assertSame($contentLength, $result);
    }

    public function testGetContentLengthFromHeadersReturnsNullWhenOffsetIsMissing(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Length'))->shouldBeCalledOnce()->willReturn(null);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->getContentLengthFromHeaders($headerBag->reveal());
        $this->assertNull($result);
    }

    public function testGetContentLengthFromHeadersThrowsWhenOffsetIsNotNumeric(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Length'))->shouldBeCalledOnce()->willReturn('123abc');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Content-Length' requires a non-negative integer as its value, got '123abc'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getContentLengthFromHeaders($headerBag->reveal());
    }

    public function testGetContentLengthFromHeadersThrowsWhenOffsetIsNegative(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Content-Length'))->shouldBeCalledOnce()->willReturn('-4321');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Content-Length' requires a non-negative integer as its value, got '-4321'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getContentLengthFromHeaders($headerBag->reveal());
    }

    public static function uploadLength(): array
    {
        return [
            ['0', 0],
            ['1', 1],
            ['1234', 1234],
            ['6543', 6543],
        ];
    }

    #[DataProvider('uploadLength')]
    public function testGetUploadLengthFromHeaders(string $headerValue, int $uploadLength): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Length'))->shouldBeCalledOnce()->willReturn($headerValue);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->getUploadLengthFromHeaders($headerBag->reveal());
        $this->assertSame($uploadLength, $result);
    }

    public function testGetUploadLengthFromHeadersReturnsNullWhenOffsetIsMissing(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Length'))->shouldBeCalledOnce()->willReturn(null);

        $headerParseService = $this->buildHeaderParseService();

        $result = $headerParseService->getUploadLengthFromHeaders($headerBag->reveal());
        $this->assertNull($result);
    }

    public function testGetUploadLengthFromHeadersThrowsWhenOffsetIsNotNumeric(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Length'))->shouldBeCalledOnce()->willReturn('123abc');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Upload-Length' requires a non-negative integer as its value, got '123abc'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getUploadLengthFromHeaders($headerBag->reveal());
    }

    public function testGetUploadLengthFromHeadersThrowsWhenOffsetIsNegative(): void
    {
        $headerBag = $this->prophesize(HeaderBag::class);
        $headerBag->get(Argument::is('Upload-Length'))->shouldBeCalledOnce()->willReturn('-4321');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("Header 'Upload-Length' requires a non-negative integer as its value, got '-4321'."))->shouldBeCalledOnce()->willReturn($exception);

        $headerParseService = $this->buildHeaderParseService(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $headerParseService->getUploadLengthFromHeaders($headerBag->reveal());
    }
}
