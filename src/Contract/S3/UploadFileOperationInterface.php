<?php

declare(strict_types=1);

namespace App\Contract\S3;

interface UploadFileOperationInterface
{
    public function getUploadBucket(): string;

    public function getUploadKey(): string;

    public function getStorageBucket(): string;

    public function getPreviousStorageKey(): ?string;

    public function getStorageKey(): string;

    /**
     * @return resource
     */
    public function getContent(): mixed;

    public function getContentLength(): ?int;

    public function getMimeType(): string;
}
