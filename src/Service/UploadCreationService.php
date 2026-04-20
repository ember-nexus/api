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
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\S3\UploadFileOperationFactory;
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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadCreationService
{
    public function __construct(
        private AuthProvider $authProvider,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private S3Service $s3Service,
        private ElementManager $elementManager,
        private UploadFileOperationFactory $uploadFileOperationFactory,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private ResumableUploadRequestFactory $resumableUploadRequestFactory,
        private EventDispatcherInterface $eventDispatcher,
        private NoContentResponseFactory $noContentResponseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private UploadService $uploadService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function handleUploadCreationFromRequest(UuidInterface $elementId, Request $request): Response
    {
        $element = $this->elementManager->getElementOrFail($elementId);

        $resumableUploadRequest = $this->resumableUploadRequestFactory->createResumableUploadRequestFromRequest($request, $elementId);

        if (false === $resumableUploadRequest->isUploadComplete()) {
            return $this->createNewResumableUpload($resumableUploadRequest);
        }

        return $this->setOrReplaceElementFileDirectly($element, $resumableUploadRequest);
    }

    private function setOrReplaceElementFileDirectly(
        NodeElementInterface|RelationElementInterface $element,
        ResumableUploadRequestInterface $resumableUploadRequest,
    ): Response {
        $uploadFileOperation = $this->uploadFileOperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest);
        $this->s3Service->uploadFile($uploadFileOperation);

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($resumableUploadRequest->getElementId()));

        $element->addProperty('file', [
            'contentLength' => $uploadFileOperation->getContentLength(),
            'extension' => $resumableUploadRequest->getExtension(),
            'mimeType' => $uploadFileOperation->getMimeType(),
        ]);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new CreatedResponse();
    }

    private function createNewResumableUpload(ResumableUploadRequestInterface $resumableUploadRequest): Response
    {
        $uploadId = Uuid::uuid4();

        $uploadOffset = 0;
        $alreadyUploadedChunks = 0;
        if (0 !== $resumableUploadRequest->getContentLength()) {
            $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId);
            $uploadOffset = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);
            $alreadyUploadedChunks = 1;
            if ($uploadOffset < $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()) {
                /**
                 * file chunk has to be bigger than <min> length, unless:
                 *   - it is the last file chunk, which can contain data of arbitrary length (max limit still applies)
                 *   - it is of zero length -> no actual content / client just asks for upload limits & starts upload process
                 */
                if (false === $resumableUploadRequest->isUploadComplete()) {
                    throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at least %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes(), $uploadOffset));
                }
            }
            if ($uploadOffset > $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes()) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at most %d bytes long, got %d.', $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes(), $uploadOffset));
            }
        }

        $expires = (new DateTime())->add(new DateInterval(sprintf('PT%sS', $this->emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest())));
        $upload = new Upload(
            $uploadId,
            $resumableUploadRequest->getUploadLength(),
            $uploadOffset,
            $resumableUploadRequest->isUploadComplete() ?? false,
            $resumableUploadRequest->getElementId(),
            $alreadyUploadedChunks,
            $this->authProvider->getUserId(),
            $resumableUploadRequest->getExtension(),
            $expires
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
