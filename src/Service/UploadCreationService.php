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
        private IncrementalHashService $incrementalHashService,
        private DigestService $digestService,
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

        $requestDigestHeaderValue = $request->headers->get('Repr-Digest') ?? $request->headers->get('Content-Digest');

        return $this->setOrReplaceElementFileDirectly($element, $resumableUploadRequest, $requestDigestHeaderValue);
    }

    private function setOrReplaceElementFileDirectly(
        NodeElementInterface|RelationElementInterface $element,
        ResumableUploadRequestInterface $resumableUploadRequest,
        ?string $requestDigestHeaderValue,
    ): Response {
        // the request body is hashed once, locally, then rewound, before anything else (including
        // UploadFileOperationFactory's own mime type sniffing) reads it - see IncrementalHashService for why this
        // is not done via a persistently-attached stream filter instead.
        $resource = $resumableUploadRequest->getContent();
        $hashContext = $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
        $this->incrementalHashService->updateFromResource($hashContext, $resource);
        $hash = $this->incrementalHashService->finalize($hashContext);

        // building the operation only sniffs the mime type locally; it does not touch S3 - so the digest, now
        // already known, can be verified before the storage bucket is ever written to. On a mismatch, this means
        // an existing file at this element is never replaced/overwritten in the first place.
        $uploadFileOperation = $this->uploadFileOperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest);

        if (null !== $requestDigestHeaderValue) {
            $this->verifyRequestDigest($requestDigestHeaderValue, $hash);
        }

        $this->s3Service->uploadFile($uploadFileOperation);
        if (is_resource($resource)) {
            \Safe\fclose($resource);
        }

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($resumableUploadRequest->getElementId()));

        $element->addProperty('file', [
            'contentLength' => $uploadFileOperation->getContentLength(),
            'extension' => $resumableUploadRequest->getExtension(),
            'mimeType' => $uploadFileOperation->getMimeType(),
            'hash' => [
                FileHashService::ALGORITHM => $hash,
            ],
        ]);
        $element->addProperty('hasFile', true);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new CreatedResponse();
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

    private function createNewResumableUpload(ResumableUploadRequestInterface $resumableUploadRequest): Response
    {
        $uploadId = Uuid::uuid4();

        $uploadOffset = 0;
        $alreadyUploadedChunks = 0;
        $hashState = null;
        if (0 !== $resumableUploadRequest->getContentLength()) {
            // the chunk is hashed once, locally, then rewound, before S3Service ever sees it; the running hash
            // state is persisted on the Upload element for the next chunk to resume from, so the final hash never
            // requires reading the file back from S3 at all.
            $resource = $resumableUploadRequest->getContent();
            $hashContext = $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
            $this->incrementalHashService->updateFromResource($hashContext, $resource);

            $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId);
            $uploadOffset = $this->s3Service->uploadFileChunk($uploadFileChunkOperation);
            if (is_resource($resource)) {
                \Safe\fclose($resource);
            }

            $hashState = $this->incrementalHashService->serializeContextForStorage($hashContext);

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
            $expires,
            $hashState
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
