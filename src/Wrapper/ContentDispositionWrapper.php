<?php

declare(strict_types=1);

namespace App\Wrapper;

use cardinalby\ContentDisposition\ContentDisposition;

/**
 * @codeCoverageIgnore
 */
class ContentDispositionWrapper
{
    public function parseContentDisposition(string $contentDisposition): ?string
    {
        return ContentDisposition::parse($contentDisposition)->getFilename();
    }
}
