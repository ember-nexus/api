<?php

declare(strict_types=1);

namespace App\Response;

use App\Service\StorageUtilService;
use App\Type\Etag;
use AsyncAws\S3\Result\GetObjectOutput;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BinaryStreamResponse extends StreamedResponse
{
    public function __construct(GetObjectOutput $object, string $fileName, string $fileNameFallback)
    {
        parent::__construct();
        $this->content = '';
        $stream = $object->getBody()->getContentAsResource();

        $this->headers->set('Content-Length', (string) ($object->getContentLength() ?? 0));
        $this->headers->set('Content-Type', 'application/octet-stream');

        $disposition = $this->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            $fileNameFallback
        );
        $this->headers->set('Content-Disposition', $disposition);

        $this->setCallback(function () use ($stream): void {
            while (!feof($stream)) {
                $buffer = \Safe\fread($stream, StorageUtilService::STREAM_CHUNK_SIZE);
                if (0 === strlen($buffer)) {
                    break;
                }
                echo $buffer;
                // todo is this comment safe to remove?
                // ob_flush();
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
