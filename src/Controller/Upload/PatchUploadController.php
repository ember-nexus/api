<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\FileHashService;
use App\Service\FileService;
use App\Service\FileSizeLimitService;
use App\Service\IncrementalHashService;
use App\Service\S3Service;
use App\Service\UploadCancellationService;
use App\Service\UploadFinalizationService;
use App\Service\UploadLockService;
use App\Service\UploadService;
use App\Type\AccessType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use HashContext;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Safe\DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class PatchUploadController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private PartialUploadRequestFactory $partialUploadRequestFactory,
        private NoContentResponseFactory $noContentResponseFactory,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadFinalizationService $uploadFinalizationService,
        private UploadCancellationService $uploadCancellationService,
        private UploadLockService $uploadLockService,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private FileOperationFactory $fileOperationFactory,
        private S3Service $s3Service,
        private IncrementalHashService $incrementalHashService,
        private FileSizeLimitService $fileSizeLimitService,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private Client410GoneExceptionFactory $client410GoneExceptionFactory,
    ) {
    }

    #[Route(
        '/upload/{id}',
        name: 'patch-upload',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PATCH']
    )]
    public function patchUpload(string $id, Request $request): Response
    {
        // cheap checks first, so that only the owner of an upload can block it with the lock
        $upload = $this->loadAuthorizedUpload($id);

        // draft-ietf-httpbis-resumable-upload: concurrent appends must not corrupt the upload
        $lockToken = $this->uploadLockService->acquire($upload->getId());
        if (null === $lockToken) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Another request is currently modifying this upload, please retry once it has finished.');
        }

        try {
            // state may have changed while waiting for the lock, so the offset check needs fresh data
            $upload = $this->loadAuthorizedUpload($id);

            return $this->appendToUpload($upload, $request);
        } finally {
            $this->uploadLockService->release($upload->getId(), $lockToken);
        }
    }

    private function loadAuthorizedUpload(string $id): UploadInterface
    {
        $uploadElement = $this->elementManager->getElementOrFail(UuidV4::fromString($id));
        try {
            $upload = $this->uploadFactory->createUploadFromElement($uploadElement);
        } catch (Exception) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $userId = $this->authProvider->getUserId();
        if ($upload->getUploadOwner()->toString() !== $userId->toString()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        if (!$this->accessChecker->hasAccessToElement($userId, $upload->getUploadTarget(), AccessType::UPDATE)) {
            // the owner lost access to the target, so the upload is cancelled as well
            $this->uploadCancellationService->cancelUpload($upload);

            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($upload->getExpires() < new DateTime()) {
            throw $this->client410GoneExceptionFactory->createFromTemplate();
        }

        return $upload;
    }

    private function appendToUpload(UploadInterface $upload, Request $request): Response
    {
        $partialUploadRequest = $this->partialUploadRequestFactory->createPartialUploadRequestFromRequest($request);

        if ($partialUploadRequest->getUploadOffset() !== $upload->getUploadOffset()) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Offset from request does not match offset of resource.', additionalProperties: ['expected-offset' => $upload->getUploadOffset(), 'provided-offset' => $partialUploadRequest->getUploadOffset()]);
        }

        // an empty intermediate chunk is a no-op which just reports the current state, e.g. to check the offset; S3
        // does not accept empty parts, so nothing is stored and neither offset nor hash state change
        $declaredChunkLength = $partialUploadRequest->getContentLength();
        if (false === $partialUploadRequest->isUploadComplete() && 0 === ($declaredChunkLength ?? $this->getBufferedContentLength($partialUploadRequest))) {
            $emptyResource = $partialUploadRequest->getContent();
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (is_resource($emptyResource)) {
                \Safe\fclose($emptyResource);
            }

            return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
        }

        // reject obviously invalid chunks before anything is sent to S3
        if (null !== $declaredChunkLength) {
            $this->assertValidChunkLength($partialUploadRequest, $upload, $declaredChunkLength);
        }

        // hashed before the upload to S3, see IncrementalHashService
        $resource = $partialUploadRequest->getContent();
        $hashState = $upload->getHashState();
        $hashContext = null !== $hashState
            ? $this->incrementalHashService->unserializeContextFromStorage($hashState)
            : $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
        $this->incrementalHashService->updateFromResource($hashContext, $resource);

        // every attempt writes its own object, so that a concurrent attempt for the same chunk can not overwrite it
        $chunkId = $this->fileService->generateUploadChunkId();
        $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromPartialUploadRequest($partialUploadRequest, $upload, $chunkId);
        $chunkLength = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);
        // the S3 client may already have closed the resource while uploading it
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_resource($resource)) {
            \Safe\fclose($resource);
        }

        try {
            // the declared Content-Length was already checked before the upload, this is the authoritative check
            $this->assertValidChunkLength($partialUploadRequest, $upload, $chunkLength);
            $nextUpload = $this->createNextUpload($partialUploadRequest, $upload, $chunkLength, $chunkId, $hashContext);
            $this->assertOffsetUnchanged($upload, $nextUpload);
        } catch (Throwable $throwable) {
            // the chunk is not part of the upload, so nothing else references its object
            $this->s3Service->deleteFile($this->fileOperationFactory->createFileOperationFromUpload($upload, $upload->getAlreadyUploadedChunks() + 1, $chunkId));

            throw $throwable;
        }
        $upload = $nextUpload;

        if ($partialUploadRequest->isUploadComplete()) {
            $this->uploadFinalizationService->finalize($upload, $this->incrementalHashService->finalize($hashContext), $request->headers->get('Repr-Digest'));
        }

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
    }

    private function createNextUpload(PartialUploadRequestInterface $partialUploadRequest, UploadInterface $upload, int $chunkLength, string $chunkId, HashContext $hashContext): UploadInterface
    {
        if ($partialUploadRequest->isUploadComplete()) {
            // the hash state is not needed anymore, the finalization uses the final hash
            $nextUpload = $this->uploadFactory->addNewChunkToUpload($upload, $chunkLength, $chunkId);

            return $this->uploadFactory->markUploadAsComplete($nextUpload);
        }

        return $this->uploadFactory->addNewChunkToUpload($upload, $chunkLength, $chunkId, $this->incrementalHashService->serializeContextForStorage($hashContext));
    }

    // defense in depth, the lock may have expired while this request was still uploading its chunk
    private function assertOffsetUnchanged(UploadInterface $upload, UploadInterface $nextUpload): void
    {
        if (!$this->uploadService->appendChunkIfOffsetMatches($upload, $nextUpload)) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Upload was modified by another request while this chunk was uploaded, please check the offset and retry.');
        }
    }

    // the request body is buffered by the request factory, so its size is known even without `Content-Length`
    private function getBufferedContentLength(PartialUploadRequestInterface $partialUploadRequest): int
    {
        return \Safe\fstat($partialUploadRequest->getContent())['size'];
    }

    private function assertValidChunkLength(PartialUploadRequestInterface $partialUploadRequest, UploadInterface $upload, int $chunkLength): void
    {
        // same as in UploadCreationService: only the final chunk may be shorter than the minimum, even empty; empty
        // intermediate chunks never get here, see appendToUpload()
        if (false === $partialUploadRequest->isUploadComplete() && $chunkLength < $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at least %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes(), $chunkLength));
        }
        if ($chunkLength > $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at most %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes(), $chunkLength));
        }

        // reject as soon as the running total exceeds the limit, not only on completion
        $this->fileSizeLimitService->assertWithinMaxFileSize($upload->getUploadOffset() + $chunkLength);

        $uploadLength = $upload->getUploadLength();
        if (null !== $uploadLength) {
            $totalLength = $upload->getUploadOffset() + $chunkLength;
            $completesUpload = true === $partialUploadRequest->isUploadComplete();
            $isTooLong = $uploadLength < $totalLength;
            $isTooShort = $completesUpload && $uploadLength > $totalLength;
            if ($completesUpload && ($isTooLong || $isTooShort)) {
                // the completed upload can never match its declared length, so nothing is left to resume
                $this->uploadService->deleteUploadAndChunks($upload);
                $this->elementManager->flush();
            }
            if ($isTooLong) {
                throw $this->client409ConflictExceptionFactory->createFromDetail('Already uploaded data exceeds defined upload length.');
            }
            if ($isTooShort) {
                throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf('Completed upload has %d bytes, but the defined upload length is %d bytes.', $totalLength, $uploadLength));
            }
        }
    }
}
