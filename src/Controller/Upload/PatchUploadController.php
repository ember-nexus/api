<?php

declare(strict_types=1);

namespace App\Controller\Upload;

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
use App\Service\S3Service;
use App\Service\UploadService;
use App\Type\AccessType;
use App\Type\Response\JsonResponse;
use App\Type\S3\FileOperation;
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
        private EventDispatcherInterface $eventDispatcher,
        private PartialUploadRequestFactory $partialUploadRequestFactory,
        private NoContentResponseFactory $noContentResponseFactory,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private MergeFileChunksOperationFactory $mergeFileChunksOperationFactory,
        private S3Service $s3Service,
        private FileHashService $fileHashService,
        private DigestService $digestService,
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
        } catch (Exception $e) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $userId = $this->authProvider->getUserId();
        if ($upload->getUploadOwner()->toString() !== $userId->toString()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        // verify that user has still update access to upload target
        if (!$this->accessChecker->hasAccessToElement($userId, $upload->getUploadTarget(), AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($upload->getExpires() < new DateTime()) {
            throw $this->client410GoneExceptionFactory->createFromTemplate();
        }

        $partialUploadRequest = $this->partialUploadRequestFactory->createPartialUploadRequestFromRequest($request);

        if ($partialUploadRequest->getUploadOffset() !== $upload->getUploadOffset()) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Offset from request does not match offset of resource.', additionalDetails: ['expected-offset' => $upload->getUploadOffset(), 'provided-offset' => $partialUploadRequest->getUploadOffset()]);
        }

        $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromPartialUploadRequest($partialUploadRequest, $upload);
        $chunkLength = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);

        if (null !== $upload->getUploadLength()) {
            if ($upload->getUploadLength() < $upload->getUploadOffset() + $chunkLength) {
                throw $this->client409ConflictExceptionFactory->createFromDetail('Already uploaded data exceeds defined upload length.');
            }
        }

        $upload = $this->uploadFactory->addNewChunkToUpload($upload, $chunkLength);
        $this->uploadService->mergeUploadElement($upload);

        if ($partialUploadRequest->isUploadComplete()) {
            // the final chunk was successfully uploaded -> we can create the file
            $upload = $this->uploadFactory->markUploadAsComplete($upload);
            $this->uploadService->mergeUploadElement($upload);
            $requestDigestHeaderValue = $request->headers->get('Repr-Digest') ?? $request->headers->get('Content-Digest');
            $this->createFile($upload, $requestDigestHeaderValue);
        }

        $this->elementManager->flush();

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
    }

    public function createFile(UploadInterface $upload, ?string $requestDigestHeaderValue = null): Response
    {
        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());
        // captured before merging: if this element did not already have a file, and the digest check below
        // rejects the upload, the just-merged (now orphaned) storage object can be safely cleaned up, since
        // nothing else can be referencing it yet.
        $isNewFile = !$element->hasProperty('file');

        $mergeFileChunksOperation = $this->mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload);
        $mergedContentLength = $this->s3Service->mergeFileChunks($mergeFileChunksOperation);
        $mergedMimeType = $this->s3Service->getMimeTypeFromMergeFileChunksOperation($mergeFileChunksOperation);

        $storageFileOperation = new FileOperation(
            $mergeFileChunksOperation->getStorageBucket(),
            $mergeFileChunksOperation->getStorageKey()
        );

        // the merged file's ETag is a hash of the parts' ETags (not a hash of its content), so the merged file has
        // to be read once, in full, to compute a real content hash
        $hash = $this->fileHashService->calculateHashFromResource($this->s3Service->getFileAsResource($storageFileOperation));

        if (null !== $requestDigestHeaderValue) {
            try {
                $this->verifyRequestDigest($requestDigestHeaderValue, $hash);
            } catch (Client400BadContentException $exception) {
                if ($isNewFile) {
                    $this->s3Service->deleteFile($storageFileOperation);
                }
                $this->s3Service->deleteFileChunks($mergeFileChunksOperation);

                throw $exception;
            }
        }

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

        return new JsonResponse([
            'upload' => 'complete',
        ]);
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
