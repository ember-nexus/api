<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type\Response;

use App\Type\ByteRange;
use App\Type\Etag;
use App\Type\Response\BinaryStreamResponse;
use AsyncAws\Core\Stream\ResultStream;
use AsyncAws\S3\Result\GetObjectOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

use function assert;

#[Small]
#[CoversClass(BinaryStreamResponse::class)]
class BinaryStreamResponseTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return resource
     */
    private function buildStreamResource(string $content)
    {
        $resource = fopen('php://memory', 'r+');
        assert(false !== $resource);
        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }

    private function buildObject(string $content, ?int $contentLength = null): GetObjectOutput
    {
        $object = $this->prophesize(GetObjectOutput::class);
        $body = $this->prophesize(ResultStream::class);
        $body->getContentAsResource()->willReturn($this->buildStreamResource($content));
        $object->getBody()->willReturn($body->reveal());
        $object->getContentLength()->willReturn($contentLength);

        return $object->reveal();
    }

    public function testFullResponseHasStatus200AndFullContentLength(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('hello world', 11), 'file.txt', 'file.txt');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('11', $response->headers->get('Content-Length'));
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertFalse($response->headers->has('Content-Range'));
    }

    public function testFullResponseDefaultsContentLengthToZeroWhenUnknown(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('hello world', null), 'file.txt', 'file.txt');

        $this->assertSame('0', $response->headers->get('Content-Length'));
    }

    public function testRangeResponseHasStatus206AndContentRange(): void
    {
        $range = new ByteRange(2, 5, 11);
        $response = new BinaryStreamResponse($this->buildObject('hello world'), 'file.txt', 'file.txt', $range);

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('4', $response->headers->get('Content-Length'));
        $this->assertSame('bytes 2-5/11', $response->headers->get('Content-Range'));
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
    }

    public function testDefaultContentTypeIsOctetStream(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('data'), 'file.txt', 'file.txt');

        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
    }

    public function testExplicitContentTypeIsUsed(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('data'), 'file.txt', 'file.txt', null, null, 'image/png');

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function testReprDigestHeaderIsSetWhenProvided(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('data'), 'file.txt', 'file.txt', null, 'sha-256=:abc123:');

        $this->assertSame('sha-256=:abc123:', $response->headers->get('Repr-Digest'));
    }

    public function testReprDigestHeaderIsAbsentWhenNotProvided(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('data'), 'file.txt', 'file.txt');

        $this->assertFalse($response->headers->has('Repr-Digest'));
    }

    public function testContentDispositionHeaderIsSetWithFileName(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('data'), 'special file.txt', 'special-file.txt');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('special-file.txt', $disposition);
    }

    public function testSetEtagFromEtagInstanceSetsEtagHeader(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('data'), 'file.txt', 'file.txt');
        $result = $response->setEtagFromEtagInstance(new Etag('"my-etag"'));

        $this->assertSame($response, $result);
        $this->assertSame('"my-etag"', $response->getEtag());
    }

    public function testStreamsFullBodyContent(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('the quick brown fox', 20), 'file.txt', 'file.txt');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame('the quick brown fox', $output);
    }

    public function testStreamsLargeContentAcrossMultipleChunks(): void
    {
        $content = str_repeat('x', BinaryStreamResponse::STREAM_CHUNK_SIZE + 100);
        $response = new BinaryStreamResponse($this->buildObject($content, strlen($content)), 'file.txt', 'file.txt');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame($content, $output);
    }

    public function testStreamsEmptyContent(): void
    {
        $response = new BinaryStreamResponse($this->buildObject('', 0), 'file.txt', 'file.txt');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
