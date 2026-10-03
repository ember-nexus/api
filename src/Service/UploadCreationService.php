<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\Request\ResumableUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\S3\S3OperationFactory;
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
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadCreationService
{
    private const string PROFILER_DIRECT_UPLOAD_LENGTH_CHECK = 'UploadCreationService:directUpload:lengthCheck';
    private const string PROFILER_DIRECT_UPLOAD_HASH_CALCULATION = 'UploadCreationService:directUpload:hashCalculation';
    private const string PROFILER_DIRECT_UPLOAD_S3_UPLOAD = 'UploadCreationService:directUpload:s3Upload';
    private const string PROFILER_CHUNK_UPLOAD_LENGTH_CHECK = 'UploadCreationService:chunkUpload:lengthCheck';
    private const string PROFILER_CHUNK_UPLOAD_HASH_CALCULATION = 'UploadCreationService:chunkUpload:hashCalculation';
    private const string PROFILER_CHUNK_UPLOAD_S3_UPLOAD = 'UploadCreationService:chunkUpload:s3Upload';

    public function __construct(
        private AuthProvider $authProvider,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private S3Service $s3Service,
        private IncrementalHashService $incrementalHashService,
        private DigestService $digestService,
        private ElementManager $elementManager,
        private S3OperationFactory $s3OperationFactory,
        private ResumableUploadRequestFactory $resumableUploadRequestFactory,
        private EventDispatcherInterface $eventDispatcher,
        private NoContentResponseFactory $noContentResponseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private UploadService $uploadService,
        private FileSizeLimitService $fileSizeLimitService,
        private UploadBodyLimitService $uploadBodyLimitService,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private UploadChunkValidator $uploadChunkValidator,
        private ElementFileDeletionService $elementFileDeletionService,
        private ElementService $elementService,
        private Stopwatch $stopwatch,
    ) {
    }

    public function handleUploadCreationFromRequest(UuidInterface $elementId, Request $request): Response
    {
        $element = $this->elementManager->getElementOrFail($elementId);

        $resumableUploadRequest = $this->resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);

        if (false === $resumableUploadRequest->isUploadComplete()) {
            return $this->createNewResumableUpload($resumableUploadRequest, $this->elementService->hasFile($element));
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
        $this->stopwatch->start(self::PROFILER_DIRECT_UPLOAD_LENGTH_CHECK);
        $contentLength = $resumableUploadRequest->getContentLength();
        if (null !== $contentLength) {
            $this->fileSizeLimitService->assertWithinMaxFileSize($contentLength);
        }
        // a single request is bound by the maximum chunk size; the content itself is bound in the request factory
        $this->uploadBodyLimitService->assertDeclaredLengthWithinLimit($contentLength);
        $this->stopwatch->stop(self::PROFILER_DIRECT_UPLOAD_LENGTH_CHECK);

        $resource = $resumableUploadRequest->getContent();
        // hashed before the upload, so that a digest mismatch never replaces an existing file
        $this->stopwatch->start(self::PROFILER_DIRECT_UPLOAD_HASH_CALCULATION);
        $hashContext = $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
        $this->incrementalHashService->updateFromResource($hashContext, $resource);
        $hash = $this->incrementalHashService->finalize($hashContext);
        $this->stopwatch->stop(self::PROFILER_DIRECT_UPLOAD_HASH_CALCULATION);

        $uploadFileOperation = $this->s3OperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest);

        foreach ($requestDigestHeaderValues as $headerName => $requestDigestHeaderValue) {
            $this->verifyRequestDigest($headerName, $requestDigestHeaderValue, $hash);
        }

        // authoritative length, the Content-Length header is optional (e.g. chunked transfer encoding)
        $this->stopwatch->start(self::PROFILER_DIRECT_UPLOAD_S3_UPLOAD);
        $uploadedContentLength = $this->s3Service->uploadFile($uploadFileOperation);
        $this->stopwatch->stop(self::PROFILER_DIRECT_UPLOAD_S3_UPLOAD);
        // the S3 client may already have closed the resource while uploading it
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_resource($resource)) {
            \Safe\fclose($resource);
        }

        // the new object is written first and the element is flushed before the previous object is deleted: a failing
        // flush leaves the old file readable (an overwrite of the same key can not be rolled back)
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

        $this->elementFileDeletionService->deletePreviousFileAfterReplace($uploadFileOperation);
        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($resumableUploadRequest->getElementId()));

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

    private function createNewResumableUpload(ResumableUploadRequestInterface $resumableUploadRequest, bool $targetHadFileAtCreation): Response
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
            // rejected before anything is sent to S3
            $this->stopwatch->start(self::PROFILER_CHUNK_UPLOAD_LENGTH_CHECK);
            $this->uploadChunkValidator->assertWithinDeclaredLength($contentLength, false, 0, $uploadLength);
            $this->stopwatch->stop(self::PROFILER_CHUNK_UPLOAD_LENGTH_CHECK);

            $this->stopwatch->start(self::PROFILER_CHUNK_UPLOAD_HASH_CALCULATION);
            $hashContext = $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
            $this->incrementalHashService->updateFromResource($hashContext, $resource);
            $this->stopwatch->stop(self::PROFILER_CHUNK_UPLOAD_HASH_CALCULATION);

            $chunkId = $this->fileService->generateUploadChunkId();
            $uploadFileChunkOperation = $this->s3OperationFactory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId, $chunkId);
            $this->stopwatch->start(self::PROFILER_CHUNK_UPLOAD_S3_UPLOAD);
            $uploadOffset = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);
            $this->stopwatch->stop(self::PROFILER_CHUNK_UPLOAD_S3_UPLOAD);
            // the S3 client may already have closed the resource while uploading it
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (is_resource($resource)) {
                \Safe\fclose($resource);
            }

            $hashState = $this->incrementalHashService->serializeContextForStorage($hashContext);

            $chunkIds = [$chunkId];
            // a single request completes the upload, so this is never the final chunk
            $this->uploadChunkValidator->assertChunkSize($uploadOffset, false, 0);
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
            $hashState,
            $targetHadFileAtCreation
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
