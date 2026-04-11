<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Factory\Response\NoContentResponseFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Response\JsonResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\FileService;
use App\Service\UploadService;
use App\Type\Upload;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Safe\DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
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
        private S3Client $s3Client,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private ElementManager $elementManager,
        private FileService $fileService,
        private EventDispatcherInterface $eventDispatcher,
        private PartialUploadRequestFactory $partialUploadRequestFactory,
        private NoContentResponseFactory $noContentResponseFactory,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private Client410GoneExceptionFactory $client410GoneExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
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

        if ($upload->getUploadOwner() !== $this->authProvider->getUserId()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($upload->getExpires() < new DateTime()) {
            throw $this->client410GoneExceptionFactory->createFromTemplate();
        }

        $partialUploadRequest = $this->partialUploadRequestFactory->createPartialUploadRequestFromRequest($request);

        if ($partialUploadRequest->getUploadOffset() !== $upload->getUploadOffset()) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('offset from request does not match offset of resource', additionalDetails: ['expected-offset' => $upload->getUploadOffset(), 'provided-offset' => $partialUploadRequest->getUploadOffset()]);
        }

        $patchResource = $request->getContent(true);

        $currentChunkIndex = $upload->getAlreadyUploadedChunks() + 1;
        $nextChunkKey = $this->fileService->getUploadBucketKey($upload->getId(), $currentChunkIndex);

        $this->s3Client->putObject([
            'Bucket' => $this->emberNexusConfiguration->getFileS3UploadBucket(),
            'Key' => $nextChunkKey,
            'Body' => $patchResource,
        ]);

        $headResult = $this->s3Client->headObject([
            'Bucket' => $this->emberNexusConfiguration->getFileS3UploadBucket(),
            'Key' => $nextChunkKey,
        ]);

        $contentLength = $headResult->getContentLength();

        if (null === $contentLength) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to read content length of created chunk.');
        }

        // update uploadelement to update chunk index +1 and upload offset + n bytes

        $canCreateFile = false;

        if (null !== $partialUploadRequest->getContentLength()
            && $partialUploadRequest->getContentLength() !== $contentLength
        ) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Issue with upload; uploaded chunk has different length than provided content length.');
        }

        if ($partialUploadRequest->isUploadComplete()) {
            // the final chunk was successfully uploaded -> we can create the file
            $canCreateFile = true;
            $upload = $this->uploadFactory->markUploadAsComplete($upload);
        }

        $upload = $this->uploadFactory->addNewChunkToUpload($upload, $contentLength);

        $this->uploadService->persistUpload($upload);

        if ($canCreateFile) {
            $this->createFile($upload);
        }

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
    }

    public function createFile(Upload $upload): Response
    {
        $targetKey = $this->fileService->getStorageBucketKey($upload->getUploadTarget(), $upload->getExtension());

        $createResult = $this->s3Client->createMultipartUpload([
            'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
            'Key' => $targetKey,
        ]);

        $multipartUploadId = $createResult->getUploadId();

        if (null === $multipartUploadId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to create multipart upload.');
        }

        $parts = [];

        try {
            for ($i = 1; $i <= $upload->getAlreadyUploadedChunks(); ++$i) {
                $sourceKey = $this->fileService->getUploadBucketKey($upload->getId(), $i);
                // todo: possible bug with upload bucket vs storage bucket; needs to be tested live
                $copyResult = $this->s3Client->uploadPartCopy([
                    'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
                    'Key' => $targetKey,
                    'UploadId' => $multipartUploadId,
                    //                    'PartNumber' => $i + 1,
                    'PartNumber' => $i,
                    'CopySource' => sprintf('%s/%s', $this->emberNexusConfiguration->getFileS3UploadBucket(), $sourceKey),
                ]);

                $copyPartResult = $copyResult->getCopyPartResult();
                if (null === $copyPartResult) {
                    throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to read copy part result.');
                }

                $parts[] = [
                    //                    'PartNumber' => $i + 1,
                    'PartNumber' => $i,
                    'ETag' => $copyPartResult->getETag(),
                ];
            }

            $this->s3Client->completeMultipartUpload([
                'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
                'Key' => $targetKey,
                'UploadId' => $multipartUploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
            ]);

            // important todos:
            // todo: delete original file, if it a) existed and b) had a different file extension
            // todo: set file property to actual element, merge and flush it?

            $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

            $element->addProperty('file', [
                //                'contentLength' => $uploadFileOperation->getContentLength(),
                'extension' => $upload->getExtension(),
            ]);
            $this->elementManager->merge($element);
            $this->elementManager->flush();
        } catch (Throwable $e) {
            /**
             * Abort multipart upload on failure.
             */
            $this->s3Client->abortMultipartUpload([
                'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
                'Key' => $targetKey,
                'UploadId' => $multipartUploadId,
            ]);

            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf("Caught exception '%s' during multipart upload.", $e->getMessage()), previous: $e);
        }

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($upload->getUploadTarget()));

        return new JsonResponse([
            'upload' => 'complete',
        ]);
    }
}
