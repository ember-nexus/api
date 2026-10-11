<?php

declare(strict_types=1);

namespace App\Type\Response;

use App\Contract\EtagCapableResponseInterface;
use App\Type\ByteRange;
use App\Type\Etag;
use AsyncAws\S3\Result\GetObjectOutput;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BinaryStreamResponse extends StreamedResponse implements EtagCapableResponseInterface
{
    public const int STREAM_CHUNK_SIZE = 65536;

    /**
     * @psalm-suppress UninitializedProperty, PossiblyNullReference
     */
    public function __construct(GetObjectOutput $object, string $fileName, string $fileNameFallback, ?ByteRange $range = null, ?string $reprDigestHeaderValue = null, ?string $contentType = null)
    {
        parent::__construct();
        $this->content = '';
        $stream = $object->getBody()->getContentAsResource();

        $this->setRangeHeaders($object, $range);
        $this->setDigestHeader($reprDigestHeaderValue);
        $this->setContentTypeHeader($contentType);
        $this->setDispositionHeader($fileName, $fileNameFallback);
        $this->setStreamCallback($stream);
    }

    private function setRangeHeaders(GetObjectOutput $object, ?ByteRange $range): void
    {
        $this->headers->set('Accept-Ranges', 'bytes');
        // the content type was detected by the server, browsers must not second-guess it
        $this->headers->set('X-Content-Type-Options', 'nosniff');

        if (null !== $range) {
            $this->setStatusCode(206);
            $this->headers->set('Content-Length', (string) $range->getLength());
            $this->headers->set('Content-Range', sprintf('bytes %d-%d/%d', $range->getStart(), $range->getEnd(), $range->getTotalLength()));
        } else {
            $this->headers->set('Content-Length', (string) ($object->getContentLength() ?? 0));
        }
    }

    private function setDigestHeader(?string $reprDigestHeaderValue): void
    {
        if (null !== $reprDigestHeaderValue) {
            // Repr-Digest describes the whole file, so it is valid for partial responses too. Content-Digest is
            // omitted, as it would require hashing the returned range.
            $this->headers->set('Repr-Digest', $reprDigestHeaderValue);
        }
    }

    private function setContentTypeHeader(?string $contentType): void
    {
        $this->headers->set('Content-Type', $contentType ?? 'application/octet-stream');
    }

    private function setDispositionHeader(string $fileName, string $fileNameFallback): void
    {
        $disposition = $this->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            $fileNameFallback
        );
        $this->headers->set('Content-Disposition', $disposition);
    }

    /**
     * @param resource $stream
     */
    private function setStreamCallback($stream): void
    {
        $this->setCallback(function () use ($stream): void {
            while (!feof($stream)) {
                $buffer = \Safe\fread($stream, self::STREAM_CHUNK_SIZE);
                if (0 === strlen($buffer)) {
                    break;
                }
                echo $buffer;
                flush();
            }
            \Safe\fclose($stream);
        });
    }

    public function setEtagFromEtagInstance(Etag $etag): static
    {
        return parent::setEtag((string) $etag);
    }
}
