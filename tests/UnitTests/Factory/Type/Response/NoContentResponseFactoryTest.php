<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\Response;

use App\Contract\UploadInterface;
use App\Factory\Type\Response\NoContentResponseFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Safe\DateTime;

#[Small]
#[CoversClass(NoContentResponseFactory::class)]
class NoContentResponseFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateNoContentResponseWithResumableUploadHeadersFromUploadWorks(): void
    {
        $expires = DateTime::createFromFormat('Y-m-d H:i:s', '2026-04-19 13:34:00');

        $upload = $this->prophesize(UploadInterface::class);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(false);
        $upload->getUploadOffset()->shouldBeCalledOnce()->willReturn(1234);
        $upload->getUploadLength()->shouldBeCalledOnce()->willReturn(null);
        $upload->getExpires()->shouldBeCalledOnce()->willReturn($expires);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest()->shouldBeCalledOnce()->willReturn(666666);
        $emberNexusConfiguration->getFileMaxFileSizeInBytes()->shouldBeCalledOnce()->willReturn(555555);
        $emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()->shouldBeCalledOnce()->willReturn(444444);
        $emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes()->shouldBeCalledOnce()->willReturn(333333);

        $noContentResponseFactory = new NoContentResponseFactory(
            $emberNexusConfiguration->reveal()
        );

        $response = $noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload->reveal());

        $headers = $response->headers;

        $this->assertSame('?0', $headers->get('Upload-Complete'));
        $this->assertSame('1234', $headers->get('Upload-Offset'));
        $this->assertNull($headers->get('Location'));
        $this->assertNull($headers->get('Upload-Length'));
        $this->assertSame('max-age=666666, max-size=555555, min-append-size=444444, max-append-size=333333', $headers->get('Upload-Limit'));
        $this->assertSame('Sun, 19 Apr 2026 13:34:00 GMT', $headers->get('Expires'));
        $this->assertSame('no-store, private', $headers->get('Cache-Control'));
    }

    public function testCreateNoContentResponseWithResumableUploadHeadersFromUploadWorksWithOptionalValuesAsWell(): void
    {
        $expires = DateTime::createFromFormat('Y-m-d H:i:s', '2026-04-20 00:00:00');

        $upload = $this->prophesize(UploadInterface::class);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(true);
        $upload->getUploadOffset()->shouldBeCalledOnce()->willReturn(1234);
        $upload->getUploadLength()->shouldBeCalledOnce()->willReturn(4567);
        $upload->getExpires()->shouldBeCalledOnce()->willReturn($expires);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest()->shouldBeCalledOnce()->willReturn(666666);
        $emberNexusConfiguration->getFileMaxFileSizeInBytes()->shouldBeCalledOnce()->willReturn(555555);
        $emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()->shouldBeCalledOnce()->willReturn(444444);
        $emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes()->shouldBeCalledOnce()->willReturn(333333);

        $noContentResponseFactory = new NoContentResponseFactory(
            $emberNexusConfiguration->reveal()
        );

        $response = $noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload->reveal(), 'new-location');

        $headers = $response->headers;

        $this->assertSame('?1', $headers->get('Upload-Complete'));
        $this->assertSame('1234', $headers->get('Upload-Offset'));
        $this->assertSame('new-location', $headers->get('Location'));
        $this->assertSame('4567', $headers->get('Upload-Length'));
        $this->assertSame('max-age=666666, max-size=555555, min-append-size=444444, max-append-size=333333', $headers->get('Upload-Limit'));
        $this->assertSame('Mon, 20 Apr 2026 00:00:00 GMT', $headers->get('Expires'));
        $this->assertSame('no-store, private', $headers->get('Cache-Control'));
    }
}
