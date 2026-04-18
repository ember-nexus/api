<?php

declare(strict_types=1);

namespace App\Contract\Request;

interface PartialUploadRequestInterface
{
    /**
     * @return resource
     */
    public function getContent();

    public function getContentType(): string;

    public function getUploadOffset(): int;

    public function isUploadComplete(): ?bool;

    public function getContentLength(): ?int;
}
