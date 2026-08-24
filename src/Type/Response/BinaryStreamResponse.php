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
    public const int STREAM_CHUNK_SIZE = 8192;

    public function __construct(GetObjectOutput $object, string $fileName, string $fileNameFallback, ?ByteRange $range = null, ?string $reprDigestHeaderValue = null, ?string $contentType = null)
    {
        parent::__construct();
        $this->content = '';
        $stream = $object->getBody()->getContentAsResource();

        $this->headers->set('Accept-Ranges', 'bytes');

        if (null !== $range) {
            $this->setStatusCode(206);
            $this->headers->set('Content-Length', (string) $range->getLength());
            $this->headers->set('Content-Range', sprintf('bytes %d-%d/%d', $range->getStart(), $range->getEnd(), $range->getTotalLength()));
        } else {
            $this->headers->set('Content-Length', (string) ($object->getContentLength() ?? 0));
        }

        if (null !== $reprDigestHeaderValue) {
            // Repr-Digest describes the full underlying resource, regardless of Range, so it applies to both full
            // and partial responses alike. Content-Digest is deliberately not set: for a full response it would
            // always be identical to Repr-Digest (no extra information), and for a partial response it would
            // require hashing just the returned range, which is not done, so it is correctly left out rather than
            // set to a value describing the wrong bytes.
            $this->headers->set('Repr-Digest', $reprDigestHeaderValue);
        }

        $this->headers->set('Content-Type', $contentType ?? 'application/octet-stream');

        $disposition = $this->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            $fileNameFallback
        );
        $this->headers->set('Content-Disposition', $disposition);

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
