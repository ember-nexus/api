<?php

declare(strict_types=1);

namespace App\Factory\Type\Request;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Type\Request\PartialUploadRequest;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class PartialUploadRequestFactory
{

    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    )
    {
    }

    public function createPartialUploadRequestFromRequest(Request $request): PartialUploadRequest
    {
        $headers = $request->headers;

        $contentType = $this->getContentTypeFromHeaders($headers);
        $uploadOffset = $this->getUploadOffsetFromHeaders($headers);
        $isUploadComplete = $this->isUploadCompleteFromHeaders($headers);
        $contentLength = $this->getContentLengthFromHeaders($headers);
        $content = $request->getContent(true);

        $partialUploadRequest = new PartialUploadRequest();
        $partialUploadRequest->setContentType($contentType);
        $partialUploadRequest->setUploadOffset($uploadOffset);
        $partialUploadRequest->setUploadComplete($isUploadComplete);
        $partialUploadRequest->setContentLength($contentLength);
        $partialUploadRequest->setContent($content);

        return $partialUploadRequest;
    }

    private function getContentTypeFromHeaders(HeaderBag $headers): string
    {
        $contentType = $headers->get('Content-Type');
        if (null === $contentType) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Endpoint requires the header 'content-type' to be present.");
        }
        if ('application/partial-upload' !== $contentType) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Expected content type 'application/partial-upload' for partial resumable uploads, got '%s'.", $contentType));
        }
        return $contentType;
    }

    private function getUploadOffsetFromHeaders(HeaderBag $headers): int
    {
        return (int)$headers->get('Upload-Offset');
    }


    private function isUploadCompleteFromHeaders(HeaderBag $headers): ?bool
    {
        $possibleValues = [
            '?0' => false,
            '?1' => true,
        ];
        $uploadCompleteHeader = $headers->get('Upload-Complete');

        if (null === $uploadCompleteHeader) {
            return null;
        }
        if (!array_key_exists($uploadCompleteHeader, $possibleValues)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Upload-Complete' must contain a boolean value, either '?0' or '?1', got '%s'.", $uploadCompleteHeader));
        }

        return $possibleValues[$uploadCompleteHeader];
    }

    private function getContentLengthFromHeaders(HeaderBag $headers): ?int
    {
        $contentLength = $headers->get('Content-Length');
        if (null === $contentLength) {
            return null;
        }
        $contentLength = (int)$contentLength;
        if ($contentLength < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Content-Length' requires a non-negative integer as its value, got '%d'.", $contentLength));
        }

        return $contentLength;
    }

}
