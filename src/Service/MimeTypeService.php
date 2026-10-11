<?php

declare(strict_types=1);

namespace App\Service;

use finfo;

class MimeTypeService
{
    public const int NECESSARY_BYTES_FOR_MIME_TYPE_DETECTION = 5 * 1024 * 1024;
    public const string DEFAULT_MIME_TYPE = 'application/octet-stream';

    /**
     * @param resource $resource
     */
    public function getMimeTypeFromResource($resource): string
    {
        $bytes = \Safe\stream_get_contents($resource, self::NECESSARY_BYTES_FOR_MIME_TYPE_DETECTION);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($bytes);

        return false === $mimeType ? self::DEFAULT_MIME_TYPE : $mimeType;
    }
}
