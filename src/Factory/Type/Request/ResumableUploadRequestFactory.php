<?php

declare(strict_types=1);

namespace App\Factory\Type\Request;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\HeaderParseService;
use App\Type\Request\ResumableUploadRequest;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;

class ResumableUploadRequestFactory
{
    public function __construct(
        private HeaderParseService $headerParseService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createResumableUploadRequestFromRequest(Request $request, UuidInterface $elementId): ResumableUploadRequest
    {
        $headers = $request->headers;

        $isUploadComplete = $this->headerParseService->isUploadCompleteFromHeaders($headers);
        $uploadLength = $this->headerParseService->getUploadLengthFromHeaders($headers);
        $contentLength = $this->headerParseService->getContentLengthFromHeaders($headers);
        $extension = $this->headerParseService->getExtensionFromHeaders($headers);
        $content = $request->getContent(true);

        if (null !== $uploadLength && null !== $contentLength && $uploadLength !== $contentLength && $isUploadComplete === true) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Inconsistent length values provided in headers 'Content-Length' and 'Upload-Length'.");
        }

        return new ResumableUploadRequest(
            $elementId,
            $content,
            $isUploadComplete,
            $uploadLength,
            $contentLength,
            $extension
        );
    }
}
