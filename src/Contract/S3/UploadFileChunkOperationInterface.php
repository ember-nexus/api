<?php

declare(strict_types=1);

namespace App\Contract\S3;

interface UploadFileChunkOperationInterface
{
    public function getUploadBucket(): string;

    public function getUploadKey(): string;

    /**
     * @return resource
     */
    public function getContent(): mixed;

    public function getContentLength(): ?int;

    public function getMimeType(): string;
}
