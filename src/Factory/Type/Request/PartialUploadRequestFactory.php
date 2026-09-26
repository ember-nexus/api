<?php

declare(strict_types=1);

namespace App\Factory\Type\Request;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\HeaderParseService;
use App\Service\UploadBodyLimitService;
use App\Type\Request\PartialUploadRequest;
use Symfony\Component\HttpFoundation\Request;

class PartialUploadRequestFactory
{
    public function __construct(
        private HeaderParseService $headerParseService,
        private UploadBodyLimitService $uploadBodyLimitService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createPartialUploadRequestFromRequest(Request $request): PartialUploadRequestInterface
    {
        $headers = $request->headers;

        $contentType = $this->headerParseService->getContentTypeFromHeaders($headers, 'application/partial-upload');
        $uploadOffset = $this->headerParseService->getUploadOffsetFromHeaders($headers);
        $isUploadComplete = $this->headerParseService->isUploadCompleteFromHeaders($headers);
        if (null === $isUploadComplete) {
            // mandatory on every append request, see draft-ietf-httpbis-resumable-upload section 4.4.1
            throw $this->client400BadContentExceptionFactory->createFromDetail("Header 'Upload-Complete' is required when appending to an upload.");
        }
        $contentLength = $this->headerParseService->getContentLengthFromHeaders($headers);
        $this->uploadBodyLimitService->assertDeclaredLengthWithinLimit($contentLength);
        $content = $this->uploadBodyLimitService->boundContent($request->getContent(true), $contentLength);

        return new PartialUploadRequest(
            $content,
            $contentType,
            $uploadOffset,
            $isUploadComplete,
            $contentLength
        );
    }
}
