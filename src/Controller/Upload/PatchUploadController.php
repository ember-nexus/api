<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\UploadInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\S3\MergeFileChunksOperationFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\DigestService;
use App\Service\ElementManager;
use App\Service\FileHashService;
use App\Service\FileSizeLimitService;
use App\Service\IncrementalHashService;
use App\Service\S3Service;
use App\Service\UploadService;
use App\Type\AccessType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Safe\DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
        private EventDispatcherInterface $eventDispatcher,
        private PartialUploadRequestFactory $partialUploadRequestFactory,
        private NoContentResponseFactory $noContentResponseFactory,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private MergeFileChunksOperationFactory $mergeFileChunksOperationFactory,
        private S3Service $s3Service,
        private IncrementalHashService $incrementalHashService,
        private DigestService $digestService,
        private FileSizeLimitService $fileSizeLimitService,
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
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($upload->getExpires() < new DateTime()) {
            throw $this->client410GoneExceptionFactory->createFromTemplate();
        }

        $partialUploadRequest = $this->partialUploadRequestFactory->createPartialUploadRequestFromRequest($request);

        if ($partialUploadRequest->getUploadOffset() !== $upload->getUploadOffset()) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Offset from request does not match offset of resource.', additionalProperties: ['expected-offset' => $upload->getUploadOffset(), 'provided-offset' => $partialUploadRequest->getUploadOffset()]);
        }

        // reject obviously invalid chunks before anything is sent to S3
        $declaredChunkLength = $partialUploadRequest->getContentLength();
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

        $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromPartialUploadRequest($partialUploadRequest, $upload);
        $chunkLength = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);
        // the S3 client may already have closed the resource while uploading it
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_resource($resource)) {
            \Safe\fclose($resource);
        }

        // the declared Content-Length was already checked before the upload, this is the authoritative check
        $this->assertValidChunkLength($partialUploadRequest, $upload, $chunkLength);

        if ($partialUploadRequest->isUploadComplete()) {
            $finalHash = $this->incrementalHashService->finalize($hashContext);
            $upload = $this->uploadFactory->addNewChunkToUpload($upload, $chunkLength);
            $upload = $this->uploadFactory->markUploadAsComplete($upload);
            $this->uploadService->mergeUploadElement($upload);
            $requestDigestHeaderValue = $request->headers->get('Repr-Digest') ?? $request->headers->get('Content-Digest');
            $this->createFile($upload, $finalHash, $requestDigestHeaderValue);
        } else {
            $hashState = $this->incrementalHashService->serializeContextForStorage($hashContext);
            $upload = $this->uploadFactory->addNewChunkToUpload($upload, $chunkLength, $hashState);
            $this->uploadService->mergeUploadElement($upload);
        }

        $this->elementManager->flush();

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
    }

    private function assertValidChunkLength(PartialUploadRequestInterface $partialUploadRequest, UploadInterface $upload, int $chunkLength): void
    {
        // same as in UploadCreationService: only the final chunk may be shorter than the minimum, even empty
        if (false === $partialUploadRequest->isUploadComplete() && $chunkLength < $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at least %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes(), $chunkLength));
        }
        if ($chunkLength > $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at most %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes(), $chunkLength));
        }

        // reject as soon as the running total exceeds the limit, not only on completion
        $this->fileSizeLimitService->assertWithinMaxFileSize($upload->getUploadOffset() + $chunkLength);

        if (null !== $upload->getUploadLength() && $upload->getUploadLength() < $upload->getUploadOffset() + $chunkLength) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Already uploaded data exceeds defined upload length.');
        }
    }

    private function createFile(UploadInterface $upload, string $hash, ?string $requestDigestHeaderValue = null): void
    {
        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

        // size and digest are verified before merging, so an existing file is never overwritten on a mismatch
        $mergeFileChunksOperation = $this->mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload);

        try {
            $this->fileSizeLimitService->assertWithinMaxFileSize($upload->getUploadOffset());
        } catch (Client400BadContentException $exception) {
            $this->s3Service->deleteFileChunks($mergeFileChunksOperation);

            throw $exception;
        }

        if (null !== $requestDigestHeaderValue) {
            try {
                $this->verifyRequestDigest($requestDigestHeaderValue, $hash);
            } catch (Client400BadContentException $exception) {
                $this->s3Service->deleteFileChunks($mergeFileChunksOperation);

                throw $exception;
            }
        }

        $mergedContentLength = $this->s3Service->mergeFileChunks($mergeFileChunksOperation);
        $mergedMimeType = $this->s3Service->getMimeTypeFromMergeFileChunksOperation($mergeFileChunksOperation);

        $element->addProperty('file', [
            'contentLength' => $mergedContentLength,
            'extension' => $upload->getExtension(),
            'mimeType' => $mergedMimeType,
            'hash' => [
                FileHashService::ALGORITHM => $hash,
            ],
        ]);
        $element->addProperty('hasFile', true);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $this->s3Service->deleteFileChunks($mergeFileChunksOperation);
        $this->uploadService->deleteUpload($upload);
        $this->elementManager->flush();

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($upload->getUploadTarget()));
    }

    private function verifyRequestDigest(string $requestDigestHeaderValue, string $actualHash): void
    {
        $expectedHash = $this->digestService->parseSha256HexFromHeaderValue($requestDigestHeaderValue);
        if (null === $expectedHash) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not verify upload: 'Repr-Digest'/'Content-Digest' header '%s' does not declare a supported digest algorithm; only 'sha-256' is supported.", $requestDigestHeaderValue));
        }
        if ($expectedHash !== $actualHash) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Could not verify upload: the declared digest does not match the uploaded file\'s actual content.');
        }
    }
}
