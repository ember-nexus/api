<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use cardinalby\ContentDisposition\ContentDisposition;
use Symfony\Component\HttpFoundation\HeaderBag;
use Throwable;

class HeaderParseService
{
    public function __construct(
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function getContentTypeFromHeaders(HeaderBag $headers, ?string $expectedContentType): string
    {
        $contentType = $headers->get('Content-Type');
        if (null === $contentType) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Endpoint requires the header 'content-type' to be present.");
        }
        $contentType = trim(strtolower(explode(';', $contentType)[0]));
        if ('' === $contentType) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Header 'Content-Type' must contain a valid MIME type, got an empty value.");
        }
        if (null !== $expectedContentType) {
            $expectedContentType = strtolower($expectedContentType);
            if ($expectedContentType !== $contentType) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Expected content type '%s' for partial resumable uploads, got '%s'.", $expectedContentType, $contentType));
            }
        }

        return $contentType;
    }

    public function getExtensionFromHeaders(HeaderBag $headers): string
    {
        $contentDisposition = $headers->get('Content-Disposition');
        if (null === $contentDisposition) {
            return FileService::DEFAULT_EXTENSION;
        }
        try {
            $parsedContentDisposition = ContentDisposition::parse($contentDisposition);
        } catch (Throwable $exception) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not parse 'Content-Disposition' header: '%s'.", $exception->getMessage()));
        }
        $fileName = $parsedContentDisposition->getFilename();
        if (null === $fileName) {
            return FileService::DEFAULT_EXTENSION;
        }
        $fileName = basename($fileName);
        $fileName = $this->fileService->removeReservedCharactersFromFileName($fileName);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (empty($extension)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not parse a file extension from the filename in 'Content-Disposition': '%s'.", $fileName));
        }

        return $extension;
    }

    public function getUploadOffsetFromHeaders(HeaderBag $headers): int
    {
        $uploadOffset = $headers->get('Upload-Offset');
        if (null === $uploadOffset) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Header 'Upload-Offset' is required.");
        }
        if (!ctype_digit($uploadOffset)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Upload-Offset' requires a non-negative integer as its value, got '%s'.", $uploadOffset));
        }
        $uploadOffset = (int) $uploadOffset;
        if ($uploadOffset < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Header 'Upload-Offset' must be a positive int.");
        }

        return $uploadOffset;
    }

    public function isUploadCompleteFromHeaders(HeaderBag $headers): ?bool
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

    public function getContentLengthFromHeaders(HeaderBag $headers): ?int
    {
        $contentLength = $headers->get('Content-Length');
        if (null === $contentLength) {
            return null;
        }
        if (!ctype_digit($contentLength)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Content-Length' requires a non-negative integer as its value, got '%s'.", $contentLength));
        }
        $contentLength = (int) $contentLength;
        if ($contentLength < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Content-Length' requires a non-negative integer as its value, got '%d'.", $contentLength));
        }

        return $contentLength;
    }

    public function getUploadLengthFromHeaders(HeaderBag $headers): ?int
    {
        $uploadLength = $headers->get('Upload-Length');
        if (null === $uploadLength) {
            return null;
        }
        if (!ctype_digit($uploadLength)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Upload-Length' requires a non-negative integer as its value, got '%s'.", $uploadLength));
        }
        $uploadLength = (int) $uploadLength;
        if ($uploadLength < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Upload-Length' requires a non-negative integer as its value, got '%d'.", $uploadLength));
        }

        return $uploadLength;
    }
}
