<?php

declare(strict_types=1);

namespace App\Contract\Request;

use Ramsey\Uuid\UuidInterface;

interface ResumableUploadRequestInterface
{
    public function getElementId(): UuidInterface;

    /**
     * @return resource
     */
    public function getContent();

    public function isUploadComplete(): ?bool;

    public function getUploadLength(): ?int;

    public function getContentLength(): ?int;

    public function getExtension(): string;
}
