<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
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
        private StorageUtilService $storageUtilService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    private function verifyElementDoesNotHaveFile(NodeElementInterface|RelationElementInterface $element): void
    {
        $properties = $element->getProperties();
        if (array_key_exists('file', $properties)) {
            throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Element with id '%s' already has an associated file; can not create new file. Delete existing file first or replace it with PUT.", $element->getId()?->toString() ?? 'missing element id'));
        }
    }

    private function getElementFromElementManager(UuidInterface $elementId): NodeElementInterface|RelationElementInterface
    {
        $element = $this->elementManager->getElement($elementId);
        if (null === $element) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $element;
    }

    private function verifyUserCanUploadFileToElement(UuidInterface $userId, UuidInterface $elementId): void
    {
        // creating files only requires update privileges to the element itself
        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
    }

    private function getIsUploadCompleteFromHeader(HeaderBag $headers): ?bool
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

    private function getUploadLengthFromHeader(HeaderBag $headers): ?int
    {
        $uploadLength = $headers->get('Upload-Length');
        if (null === $uploadLength) {
            return null;
        }
        $uploadLength = (int) $uploadLength;
        if ($uploadLength < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Upload-Length' requires a non-negative integer as its value, got '%d'.", $uploadLength));
        }

        return $uploadLength;
    }

    private function getContentLengthFromHeader(HeaderBag $headers): ?int
    {
        $contentLength = $headers->get('Content-Length');
        if (null === $contentLength) {
            return null;
        }
        $contentLength = (int) $contentLength;
        if ($contentLength < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Header 'Content-Length' requires a non-negative integer as its value, got '%d'.", $contentLength));
        }

        return $contentLength;
    }

    private function setOrReplaceElementFileDirectly(NodeElementInterface|RelationElementInterface $element, Request $request): Response
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Expected property $element to contain non-null element id, got null.');
        }

        $uploadLength = $this->getUploadLengthFromHeader($request->headers);
        $contentLengthHeaderValue = $this->getContentLengthFromHeader($request->headers);
        if (null !== $contentLengthHeaderValue && null !== $uploadLength) {
            if ($contentLengthHeaderValue !== $uploadLength) {
                throw $this->client400BadContentExceptionFactory->createFromDetail("Inconsistent length values provided in headers 'Content-Length' and 'Upload-Length'.");
            }
        }

        $uploadBucket = $this->emberNexusConfiguration->getFileS3UploadBucket();
        $uploadKey = $this->storageUtilService->getUploadBucketKey($elementId);

        $this->s3Client->putObject([
            'Bucket' => $uploadBucket,
            'Key' => $uploadKey,
            'Body' => $request->getContent(true),
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

        // todo: delete original file with original extension
        // todo: set extension of current upload
        // todo: implement mimetype detection

        $copyResult = $this->s3Client->copyObject([
            'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
            'Key' => $this->storageUtilService->getStorageBucketKey($elementId),
            'CopySource' => sprintf(
                '%s/%s',
                $uploadBucket,
                $uploadKey
            ),
        ]);

        try {
            $copyResult->resolve();
            $deleteResult = $this->s3Client->deleteObject([
                'Bucket' => $uploadBucket,
                'Key' => $uploadKey,
            ]);
            $deleteResult->resolve();
        } catch (Throwable $e) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Upload failed: %s', $e->getMessage()), previous: $e);
        }

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
        $key = $this->storageUtilService->getUploadBucketKey($uploadId);

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
