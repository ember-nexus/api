<?php

declare(strict_types=1);

namespace App\Factory\Type\Request;

use App\Service\HeaderParseService;
use App\Type\Request\PartialUploadRequest;
use Symfony\Component\HttpFoundation\Request;

class PartialUploadRequestFactory
{
    public function __construct(
        private HeaderParseService $headerParseService,
    ) {
    }

    public function createPartialUploadRequestFromRequest(Request $request): PartialUploadRequest
    {
        $headers = $request->headers;

        $contentType = $this->headerParseService->getContentTypeFromHeaders($headers, 'application/partial-upload');
        $uploadOffset = $this->headerParseService->getUploadOffsetFromHeaders($headers);
        $isUploadComplete = $this->headerParseService->isUploadCompleteFromHeaders($headers);
        $contentLength = $this->headerParseService->getContentLengthFromHeaders($headers);
        $content = $request->getContent(true);

        return new PartialUploadRequest(
            $content,
            $contentType,
            $uploadOffset,
            $isUploadComplete,
            $contentLength
        );
    }
}
