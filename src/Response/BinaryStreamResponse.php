<?php

declare(strict_types=1);

namespace App\Response;

use App\Service\StorageUtilService;
use App\Type\Etag;
use AsyncAws\S3\Result\GetObjectOutput;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BinaryStreamResponse extends StreamedResponse
{
    public function __construct(GetObjectOutput $object)
    {
        parent::__construct();
        $stream = $object->getBody()->getContentAsResource();

        $this->headers->set('Content-Length', (string) ($object->getContentLength() ?? 0));
        // todo: add content disposition header?
        $this->headers->set('Content-Type', 'application/octet-stream');

        $this->setCallback(function () use ($stream): void {
            while (!feof($stream)) {
                $buffer = fread($stream, StorageUtilService::STREAM_CHUNK_SIZE);
                if (false === $buffer || 0 === strlen($buffer)) {
                    break;
                }
                echo $buffer;
                // todo is this comment safe to remove?
                // ob_flush();
                flush();
            }
            fclose($stream);
        });
    }

    public function setEtagFromEtagInstance(Etag $etag): static
    {
        return parent::setEtag((string) $etag);
    }
}
