<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Factory\Type\Request\ResumableUploadRequestFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\S3\UploadFileOperationFactory;
use App\Response\CreatedResponse;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Security\UploadAccessChecker;
use App\Type\Request\ResumableUploadRequest;
use App\Type\UploadElement;
use DateInterval;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadCreationService
{
    public function __construct(
        private AuthProvider $authProvider,
        private UploadAccessChecker $uploadAccessChecker,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private S3Service $s3Service,
        private ElementManager $elementManager,
        private UploadFileOperationFactory $uploadFileOperationFactory,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private ResumableUploadRequestFactory $resumableUploadRequestFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handleUploadCreationFromRequest(UuidInterface $elementId, Request $request): Response
    {
        $userId = $this->authProvider->getUserId();
        $this->uploadAccessChecker->verifyUserCanUploadFileToElement($userId, $elementId);
        $element = $this->elementManager->getElementOrFail($elementId);

        $resumableUploadRequest = $this->resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);

        if (false === $resumableUploadRequest->isUploadComplete()) {
            return $this->createNewResumableUpload($resumableUploadRequest, $userId);
        }

        return $this->setOrReplaceElementFileDirectly($element, $resumableUploadRequest);
    }

    private function setOrReplaceElementFileDirectly(
        NodeElementInterface|RelationElementInterface $element,
        ResumableUploadRequest $resumableUploadRequest,
    ): Response {
        $uploadFileOperation = $this->uploadFileOperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest);
        $this->s3Service->uploadFile($uploadFileOperation);

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($resumableUploadRequest->getElementId()));

        // todo: replace manual array with fileProperty instance
        $element->addProperty('file', [
            'contentLength' => $uploadFileOperation->getContentLength(),
        ]);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new CreatedResponse();
    }

    private function createNewResumableUpload(ResumableUploadRequest $resumableUploadRequest, UuidInterface $userId): Response
    {
        $expires = (new DateTime())->add(new DateInterval(sprintf('PT%sS', $this->emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest())));

        $uploadId = Uuid::uuid4();
        // todo: try to parse extension, and persist it
        $uploadElement = new UploadElement();
        $uploadElement
            ->setId($uploadId)
            ->setUploadOwner($userId)
            ->setUploadTarget($resumableUploadRequest->getElementId())
            ->setExtension($resumableUploadRequest->getExtension())
            ->setExpires($expires)
            ->setUploadLength($resumableUploadRequest->getUploadLength());

        $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest);
        $this->s3Service->uploadFileChunk($uploadFileChunkOperation);

        $this->elementManager->merge($uploadElement);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
