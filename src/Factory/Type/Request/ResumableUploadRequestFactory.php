<?php

declare(strict_types=1);

namespace App\Factory\Type\Request;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\HeaderParseService;
use App\Type\Request\ResumableUploadRequest;
use Symfony\Component\HttpFoundation\Request;

class ResumableUploadRequestFactory
{

    public function __construct(
        private HeaderParseService $headerParseService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    )
    {
    }

    public function createResumableUploadRequestFromRequest(Request $request): ResumableUploadRequest
    {
        $headers = $request->headers;

        $isUploadComplete = $this->headerParseService->isUploadCompleteFromHeaders($headers);
        $uploadLength = $this->headerParseService->getUploadLengthFromHeaders($headers);
        $contentLength = $this->headerParseService->getContentLengthFromHeaders($headers);
        $extension = $this->headerParseService->getExtensionFromHeaders($headers);
        $content = $request->getContent(true);

        if ($uploadLength !== null && $contentLength !== null && $uploadLength !== $contentLength) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Inconsistent length values provided in headers 'Content-Length' and 'Upload-Length'.");
        }

        $resumableUploadRequest = new ResumableUploadRequest();
        $resumableUploadRequest->setIsUploadComplete($isUploadComplete);
        $resumableUploadRequest->setUploadLength($uploadLength);
        $resumableUploadRequest->setContentLength($contentLength);
        $resumableUploadRequest->setExtension($extension);
        $resumableUploadRequest->setContent($content);

        return $resumableUploadRequest;
    }

}
