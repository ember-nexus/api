<?php

declare(strict_types=1);

namespace App\Factory\Type\Request;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Type\Request\ResumableUploadRequest;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class ResumableUploadRequestFactory
{

    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    )
    {
    }

    public function createResumableUploadRequestFromRequest(Request $request): ResumableUploadRequest
    {
        $headers = $request->headers;

        $isUploadComplete = $this->getIsUploadCompleteFromHeaders($headers);
        $uploadLength = $this->getUploadLengthFromHeaders($headers);
        $contentLength = $this->getContentLengthFromHeaders($headers);
        $content = $request->getContent(true);

        if ($uploadLength !== null && $contentLength !== null && $uploadLength !== $contentLength) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Inconsistent length values provided in headers 'Content-Length' and 'Upload-Length'.");
        }

        $resumableUploadRequest = new ResumableUploadRequest();
        $resumableUploadRequest->setIsUploadComplete($isUploadComplete);
        $resumableUploadRequest->setUploadLength($uploadLength);
        $resumableUploadRequest->setContentLength($contentLength);
        $resumableUploadRequest->setContent($content);

        return $resumableUploadRequest;
    }


    private function getIsUploadCompleteFromHeaders(HeaderBag $headers): ?bool
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

    private function getUploadLengthFromHeaders(HeaderBag $headers): ?int
    {
        $uploadLength = $headers->get('Upload-Length');
        if (null === $uploadLength) {
            return null;
        }
        $uploadLength = (int)$uploadLength;
        if ($uploadLength < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Upload-Length' requires a non-negative integer as its value, got '%d'.", $uploadLength));
        }

        return $uploadLength;
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
