<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use Psr\Log\LoggerInterface;

/**
 * The chunk list of an upload lives in MongoDB, its last chunk id and offset live in the graph (see
 * {@see UploadService::appendChunkIfOffsetMatches()}). Both are written one after another, so an outage between the
 * two writes leaves an upload whose chunk list lacks a chunk which the offset and hash already contain. Such an
 * upload can never produce the file the client sent, so it is removed instead of being continued.
 */
class UploadConsistencyService
{
    public function __construct(
        private ElementManager $elementManager,
        private UploadService $uploadService,
        private S3Service $s3Service,
        private S3OperationFactory $s3OperationFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \App\Exception\Client409ConflictException if the upload is inconsistent; the upload and its chunks are deleted
     */
    public function assertConsistent(NodeElementInterface|RelationElementInterface $uploadElement, UploadInterface $upload): void
    {
        $storedLastChunkId = $uploadElement->hasProperty('lastChunkId') ? $uploadElement->getProperty('lastChunkId') : null;
        if (null !== $storedLastChunkId && !is_string($storedLastChunkId)) {
            $storedLastChunkId = false;
        }

        $chunkIds = $upload->getChunkIds();
        $isConsistent = $storedLastChunkId === $upload->getLastChunkId()
            // an empty chunk list and a zero offset must coincide; neither can hold without the other
            && ([] === $chunkIds) === (0 === $upload->getUploadOffset());
        if ($isConsistent) {
            return;
        }

        $this->logger->error(sprintf(
            'Upload %s is inconsistent (last chunk id in graph: %s, last entry of chunk list: %s, offset: %d, chunks: %d), deleting it.',
            $upload->getId()->toString(),
            is_string($storedLastChunkId) ? $storedLastChunkId : 'invalid',
            $upload->getLastChunkId() ?? 'none',
            $upload->getUploadOffset(),
            count($chunkIds)
        ));
        // the chunk which was written last may be missing in the list, but its object exists
        if (is_string($storedLastChunkId) && !in_array($storedLastChunkId, $chunkIds, true)) {
            $this->s3Service->deleteFile($this->s3OperationFactory->createFileOperationFromUpload($upload, count($chunkIds) + 1, $storedLastChunkId));
        }
        $this->uploadService->deleteUploadAndChunks($upload);
        $this->elementManager->flush();

        throw $this->client409ConflictExceptionFactory->createFromDetail('Upload state is inconsistent and was deleted, please restart the upload.');
    }
}
