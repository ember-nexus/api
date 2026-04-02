<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\CreatedResponse;
use App\Response\NoContentResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\AccessType;
use App\Type\UploadElement;
use AsyncAws\S3\S3Client;
use DateInterval;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadCreationService
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private S3Client $s3Client,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }


    private function getElementFromElementManager(UuidInterface $elementId): NodeElementInterface|RelationElementInterface
    {
        $element = $this->elementManager->getElement($elementId);
        if (null === $element) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $element;
    }


    private function setOrReplaceElementFileDirectly(NodeElementInterface|RelationElementInterface $element, Request $request): Response
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Expected property $element to contain non-null element id, got null.');
        }

        $previousStorageKey = null;
        if ($element->hasProperty('file')) {
            $previousExtension = $this->elementService->getFileNameExtension($element);
            $previousStorageKey = $this->fileService->getStorageBucketKey($elementId, $previousExtension);
        }

//        $uploadLength = $this->getUploadLengthFromHeader($request->headers);
        $contentLengthHeaderValue = $this->getContentLengthFromHeader($request->headers);
//        if (null !== $contentLengthHeaderValue && null !== $uploadLength) {
//            if ($contentLengthHeaderValue !== $uploadLength) {
//                throw $this->client400BadContentExceptionFactory->createFromDetail("Inconsistent length values provided in headers 'Content-Length' and 'Upload-Length'.");
//            }
//        }

        // todo: make sure that existing uploads to not result in conflict; i.e. either cancel existing upload or block
        //       new upload?

        $uploadBucket = $this->emberNexusConfiguration->getFileS3UploadBucket();
        $uploadKey = $this->fileService->getUploadBucketKey($elementId, 0);

        $uploadResource = $request->getContent(true);
        $mimeType = $this->fileService->getMimeTypeFromResource($uploadResource);
        $this->s3Client->putObject([
            'Bucket' => $uploadBucket,
            'Key' => $uploadKey,
            'Body' => $uploadResource,
            'ContentType' => $mimeType
        ]);

        $headResult = $this->s3Client->headObject([
            'Bucket' => $uploadBucket,
            'Key' => $uploadKey,
        ]);

        $contentLength = $headResult->getContentLength();

        if (null === $contentLength) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to read content length of created chunk.');
        }

        if (null !== $contentLengthHeaderValue) {
            if ($contentLengthHeaderValue !== $contentLength) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Inconsistent length values between header 'Content-Length' (%d) and actual content (%d) detected.", $contentLengthHeaderValue, $contentLength));
            }
        }

        // todo: set extension of current upload

        $newExtension = 'todo';

        $newStorageKey = $this->fileService->getStorageBucketKey($elementId, $newExtension);
        $copyResult = $this->s3Client->copyObject([
            'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
            'Key' => $newStorageKey,
            'CopySource' => sprintf(
                '%s/%s',
                $uploadBucket,
                $uploadKey
            ),
        ]);

        try {
            $copyResult->resolve();
            if ($previousStorageKey !== $newStorageKey) {
                $objectConfig = [
                    'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
                    'Key' => $previousStorageKey,
                ];
                $status = $this->s3Client->objectExists($objectConfig);

                if ($status->isSuccess()) {
                    $this->s3Client->deleteObject($objectConfig);
                }
            }
            $deleteResult = $this->s3Client->deleteObject([
                'Bucket' => $uploadBucket,
                'Key' => $uploadKey,
            ]);
            $deleteResult->resolve();
        } catch (Throwable $e) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Upload failed: %s', $e->getMessage()), previous: $e);
        }

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($elementId));

        // todo: replace manual array with fileProperty instance
        $element->addProperty('file', [
            'contentLength' => $contentLength,
        ]);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new CreatedResponse();
    }

    private function createNewResumableUpload(UuidInterface $elementId, Request $request, UuidInterface $userId): Response
    {
        $expires = (new DateTime())->add(new DateInterval(sprintf('PT%sS', $this->emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest())));

        $uploadId = Uuid::uuid4();
        // todo: try to parse extension, and persist it
        $uploadElement = new UploadElement();
        $uploadElement
            ->setId($uploadId)
            ->setUploadOwner($userId)
            ->setUploadTarget($elementId)
            ->setExpires($expires);

        $uploadLength = $this->getUploadLengthFromHeader($request->headers);
        if (null !== $uploadLength) {
            $uploadElement->setUploadLength($uploadLength);
        }

        $contentLengthHeaderValue = $this->getContentLengthFromHeader($request->headers);

        $bucket = $this->emberNexusConfiguration->getFileS3UploadBucket();
        $key = $this->fileService->getUploadBucketKey($uploadId, 0);

        $this->s3Client->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => $request->getContent(true),
        ]);

        $headResult = $this->s3Client->headObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        $contentLength = $headResult->getContentLength();

        if (null === $contentLength) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to read content length of created chunk.');
        }

        if (null !== $contentLengthHeaderValue) {
            if ($contentLengthHeaderValue !== $contentLength) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Inconsistent length values between header 'Content-Length' (%d) and actual content (%d) detected.", $contentLengthHeaderValue, $contentLength));
            }
        }

        $this->elementManager->merge($uploadElement);
        $this->elementManager->flush();

        return new NoContentResponse();
    }

    public function handleUploadCreationFromRequest(UuidInterface $elementId, Request $request): Response
    {
        $userId = $this->authProvider->getUserId();
        $this->verifyUserCanUploadFileToElement($userId, $elementId);
        $element = $this->getElementFromElementManager($elementId);

        $method = $request->getMethod();
        if (!in_array($method, ['POST', 'PUT'])) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Endpoint must use HTTP method 'POST' or 'PUT', but got '%s'.", $method));
        }
        if ('POST' === $method) {
            $this->verifyElementDoesNotHaveFile($element);
        }

        $isUploadComplete = $this->getIsUploadCompleteFromHeader($request->headers);

        if (null === $isUploadComplete || true === $isUploadComplete) {
            return $this->setOrReplaceElementFileDirectly($element, $request);
        }

        return $this->createNewResumableUpload($elementId, $request, $userId);
    }
}
