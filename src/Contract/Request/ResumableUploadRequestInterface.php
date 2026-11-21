<?php

declare(strict_types=1);

namespace App\Contract\Request;

use App\Type\Uuid;

interface ResumableUploadRequestInterface
{
    public function getElementId(): Uuid;

    /**
     * @return resource
     */
    public function getContent(): mixed;

    public function isUploadComplete(): ?bool;

    public function getUploadLength(): ?int;

    public function getContentLength(): ?int;

    public function getExtension(): string;
}
