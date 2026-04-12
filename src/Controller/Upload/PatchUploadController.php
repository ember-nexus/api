<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Response\NoContentResponseFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\S3\MergeFileChunksOperationFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Response\JsonResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\S3Service;
use App\Service\UploadService;
use App\Type\Upload;
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
        private ElementManager $elementManager,
        private EventDispatcherInterface $eventDispatcher,
        private PartialUploadRequestFactory $partialUploadRequestFactory,
        private NoContentResponseFactory $noContentResponseFactory,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private MergeFileChunksOperationFactory $mergeFileChunksOperationFactory,
        private S3Service $s3Service,
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

        if ($upload->getUploadOwner()->toString() !== $this->authProvider->getUserId()->toString()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($upload->getExpires() < new DateTime()) {
            throw $this->client410GoneExceptionFactory->createFromTemplate();
        }

        $partialUploadRequest = $this->partialUploadRequestFactory->createPartialUploadRequestFromRequest($request);

        if ($partialUploadRequest->getUploadOffset() !== $upload->getUploadOffset()) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('offset from request does not match offset of resource', additionalDetails: ['expected-offset' => $upload->getUploadOffset(), 'provided-offset' => $partialUploadRequest->getUploadOffset()]);
        }

        $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromPartialUploadRequest($partialUploadRequest, $upload);
        $chunkLength = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);
        $upload = $this->uploadFactory->addNewChunkToUpload($upload, $chunkLength);
        $this->uploadService->mergeUploadElement($upload);

        if ($partialUploadRequest->isUploadComplete()) {
            // the final chunk was successfully uploaded -> we can create the file
            $upload = $this->uploadFactory->markUploadAsComplete($upload);
            $this->uploadService->mergeUploadElement($upload);
            $this->createFile($upload);
        }

        $this->elementManager->flush();

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
    }

    public function createFile(Upload $upload): Response
    {
        $mergeFileChunksOperation = $this->mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload);
        $mergedContentLength = $this->s3Service->mergeFileChunks($mergeFileChunksOperation);

        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

        $element->addProperty('file', [
            'contentLength' => $mergedContentLength,
            'extension' => $upload->getExtension(),
        ]);
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
}
