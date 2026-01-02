<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Helper\Regex;
use App\Response\JsonResponse;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\StorageUtilService;
use App\Type\UploadElement;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Safe\DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class PatchUploadController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private S3Client $s3Client,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private ElementManager $elementManager,
        private StorageUtilService $storageUtilService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
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
        $uploadId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        $uploadElement = $this->elementManager->getElement($uploadId);
        if (null === $uploadElement) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        try {
            $uploadElement = UploadElement::createFromElement($uploadElement);
        } catch (Exception $e) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (null === $uploadElement->getUploadOwner()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($uploadElement->getUploadOwner() !== $userId) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($uploadElement->getExpires() < new DateTime()) {
            throw $this->client410GoneExceptionFactory->createFromTemplate();
        }

        $requestContentType = $request->headers->get('Content-Type');
        if (null === $requestContentType) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Endpoint requires the header 'content-type' to be present.");
        }
        if ('application/partial-upload' !== $requestContentType) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Expected content type 'application/partial-upload' for partial resumable uploads, got '%s'.", $requestContentType));
        }

        $requestUploadOffsetHeader = (int) $request->headers->get('Upload-Offset');
        if ($requestUploadOffsetHeader !== $uploadElement->getUploadOffset()) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('offset from request does not match offset of resource', additionalDetails: ['expected-offset' => $uploadElement->getUploadOffset(), 'provided-offset' => $requestUploadOffsetHeader]);
        }

        $uploadTarget = $uploadElement->getUploadTarget();
        if (null === $uploadTarget) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Upload element does not contain target.');
        }

        $patchResource = $request->getContent(true);

        $currentChunkIndex = $uploadElement->getAlreadyUploadedChunks();
        $nextChunkKey = $this->storageUtilService->getUploadBucketKey($uploadId, $currentChunkIndex);

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

        $canCreateFile = false;

        $requestUploadCompleteHeader = $request->headers->get('Upload-Complete');
        $requestContentLengthHeader = (int) $request->headers->get('Content-Length');
        if ('?1' === $requestUploadCompleteHeader) {
            if ($requestContentLengthHeader === $contentLength) {
                // the final chunk was successfully uploaded -> we can create the file
                $canCreateFile = true;
                $uploadElement->setUploadComplete(true);
            }
        }
        $uploadElement->setUploadOffset($uploadElement->getUploadOffset() + $contentLength);
        $uploadElement->setAlreadyUploadedChunks($uploadElement->getAlreadyUploadedChunks() + 1);

        $this->elementManager->merge($uploadElement);
        $this->elementManager->flush();

        if ($canCreateFile) {
            $targetKey = $this->storageUtilService->getStorageBucketKey($uploadTarget);

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
                for ($i = 0; $i <= $currentChunkIndex; ++$i) {
                    $sourceKey = $this->storageUtilService->getUploadBucketKey($uploadId, $i);
                    $copyResult = $this->s3Client->uploadPartCopy([
                        'Bucket' => $this->emberNexusConfiguration->getFileS3UploadBucket(),
                        'Key' => $targetKey,
                        'UploadId' => $multipartUploadId,
                        'PartNumber' => $i + 1,
                        'CopySource' => sprintf('%s/%s', $this->emberNexusConfiguration->getFileS3UploadBucket(), $sourceKey),
                    ]);

                    $copyPartResult = $copyResult->getCopyPartResult();
                    if (null === $copyPartResult) {
                        throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to read copy part result.');
                    }

                    $parts[] = [
                        'PartNumber' => $i + 1,
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

            // upload complete

            return new JsonResponse([
                'upload' => 'complete',
            ]);
        }

        header(sprintf('Upload-Complete: ?%s', $uploadElement->isUploadComplete() ? '1' : '0'));
        header(sprintf('Upload-Offset: %d', $uploadElement->getUploadOffset()));
        $uploadLength = $uploadElement->getUploadLength();
        if (null !== $uploadLength) {
            header(sprintf('Upload-Length: %d', $uploadLength));
        }
        header(sprintf(
            'Upload-Limit: max-age=%d, max-size=%d, min-append-size=%d, max-append-size=%d',
            $this->emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest(),
            $this->emberNexusConfiguration->getFileMaxFileSizeInBytes(),
            $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes(),
            $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes(),
        ));
        header('Cache-Control: no-store');

        return new NoContentResponse();
    }
}
