<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\Request\ResumableUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\S3\UploadFileOperationFactory;
use App\Security\AuthProvider;
use App\Type\Response\CreatedResponse;
use App\Type\Upload;
use DateInterval;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadCreationService
{
    public function __construct(
        private AuthProvider $authProvider,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private S3Service $s3Service,
        private IncrementalHashService $incrementalHashService,
        private DigestService $digestService,
        private ElementManager $elementManager,
        private UploadFileOperationFactory $uploadFileOperationFactory,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private ResumableUploadRequestFactory $resumableUploadRequestFactory,
        private EventDispatcherInterface $eventDispatcher,
        private NoContentResponseFactory $noContentResponseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private UploadService $uploadService,
        private FileSizeLimitService $fileSizeLimitService,
        private UploadBodyLimitService $uploadBodyLimitService,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
    ) {
    }

    public function handleUploadCreationFromRequest(UuidInterface $elementId, Request $request): Response
    {
        $element = $this->elementManager->getElementOrFail($elementId);

        $resumableUploadRequest = $this->resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);

        if (false === $resumableUploadRequest->isUploadComplete()) {
            return $this->createNewResumableUpload($resumableUploadRequest);
        }

        // the body of a single request is the whole file, so `Repr-Digest` and `Content-Digest` describe the same bytes
        // and every supplied one has to match
        $requestDigestHeaderValues = [];
        foreach (['Repr-Digest', 'Content-Digest'] as $headerName) {
            $headerValue = $request->headers->get($headerName);
            if (null !== $headerValue) {
                $requestDigestHeaderValues[$headerName] = $headerValue;
            }
        }

        return $this->setOrReplaceElementFileDirectly($element, $resumableUploadRequest, $requestDigestHeaderValues);
    }

    /**
     * @param array<string, string> $requestDigestHeaderValues digest header values by header name
     */
    private function setOrReplaceElementFileDirectly(
        NodeElementInterface|RelationElementInterface $element,
        ResumableUploadRequestInterface $resumableUploadRequest,
        array $requestDigestHeaderValues,
    ): Response {
        $contentLength = $resumableUploadRequest->getContentLength();
        if (null !== $contentLength) {
            $this->fileSizeLimitService->assertWithinMaxFileSize($contentLength);
        }
        // a single request is bound by the maximum chunk size; the content itself is bound in the request factory
        $this->uploadBodyLimitService->assertDeclaredLengthWithinLimit($contentLength);

        $resource = $resumableUploadRequest->getContent();
        // hashed before the upload, so that a digest mismatch never replaces an existing file
        $hashContext = $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
        $this->incrementalHashService->updateFromResource($hashContext, $resource);
        $hash = $this->incrementalHashService->finalize($hashContext);

        $uploadFileOperation = $this->uploadFileOperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest);

        foreach ($requestDigestHeaderValues as $headerName => $requestDigestHeaderValue) {
            $this->verifyRequestDigest($headerName, $requestDigestHeaderValue, $hash);
        }

        // authoritative length, the Content-Length header is optional (e.g. chunked transfer encoding)
        $uploadedContentLength = $this->s3Service->uploadFile($uploadFileOperation);
        // the S3 client may already have closed the resource while uploading it
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_resource($resource)) {
            \Safe\fclose($resource);
        }

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($resumableUploadRequest->getElementId()));

        $element->addProperty('file', [
            'contentLength' => $uploadedContentLength,
            'extension' => $resumableUploadRequest->getExtension(),
            'mimeType' => $uploadFileOperation->getMimeType(),
            'hash' => [
                FileHashService::ALGORITHM => $hash,
            ],
        ]);
        $element->addProperty('hasFile', true);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new CreatedResponse();
    }

    private function verifyRequestDigest(string $headerName, string $requestDigestHeaderValue, string $actualHash): void
    {
        $expectedHash = $this->digestService->parseSha256HexFromHeaderValue($requestDigestHeaderValue);
        if (null === $expectedHash) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not verify upload: '%s' header '%s' does not declare a supported digest algorithm; only 'sha-256' is supported.", $headerName, $requestDigestHeaderValue));
        }
        if ($expectedHash !== $actualHash) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Could not verify upload: the digest declared in \'%s\' does not match the uploaded file\'s actual content.', $headerName));
        }
    }

    private function createNewResumableUpload(ResumableUploadRequestInterface $resumableUploadRequest): Response
    {
        $uploadLength = $resumableUploadRequest->getUploadLength();
        if (null !== $uploadLength) {
            $this->fileSizeLimitService->assertWithinMaxFileSize($uploadLength);
        }

        $uploadId = Uuid::uuid4();

        $uploadOffset = 0;
        $chunkIds = [];
        $hashState = null;
        $resource = $resumableUploadRequest->getContent();
        // the body is buffered by the request factory, so its size is known even without `Content-Length`
        $contentLength = $resumableUploadRequest->getContentLength() ?? \Safe\fstat($resource)['size'];
        if (0 === $contentLength) {
            // an empty first chunk only creates the upload, S3 does not accept empty parts
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (is_resource($resource)) {
                \Safe\fclose($resource);
            }
        } else {
            if (null !== $uploadLength && $contentLength > $uploadLength) {
                throw $this->client409ConflictExceptionFactory->createFromDetail('Already uploaded data exceeds defined upload length.');
            }
            $hashContext = $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
            $this->incrementalHashService->updateFromResource($hashContext, $resource);

            $chunkId = $this->fileService->generateUploadChunkId();
            $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId, $chunkId);
            $uploadOffset = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);
            // the S3 client may already have closed the resource while uploading it
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (is_resource($resource)) {
                \Safe\fclose($resource);
            }

            $hashState = $this->incrementalHashService->serializeContextForStorage($hashContext);

            $chunkIds = [$chunkId];
            if ($uploadOffset < $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()) {
                // only the last chunk may be smaller than the minimum chunk size
                if (false === $resumableUploadRequest->isUploadComplete()) {
                    throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at least %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes(), $uploadOffset));
                }
            }
            if ($uploadOffset > $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes()) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at most %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes(), $uploadOffset));
            }
            $this->fileSizeLimitService->assertWithinMaxFileSize($uploadOffset);
        }

        $expires = (new DateTime())->add(new DateInterval(sprintf('PT%sS', $this->emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest())));
        $upload = new Upload(
            $uploadId,
            $resumableUploadRequest->getUploadLength(),
            $uploadOffset,
            $resumableUploadRequest->isUploadComplete() ?? false,
            $resumableUploadRequest->getElementId(),
            $chunkIds,
            $this->authProvider->getUserId(),
            $resumableUploadRequest->getExtension(),
            $expires,
            $hashState
        );

        $this->uploadService->mergeUploadElement($upload);
        $this->elementManager->flush();

        $location = $this->urlGenerator->generate(
            'head-upload',
            [
                'id' => $uploadId->toString(),
            ]
        );

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload, $location);
    }
}
