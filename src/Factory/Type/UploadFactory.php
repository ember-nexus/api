<?php

declare(strict_types=1);

namespace App\Factory\Type;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\PropertyParseService;
use App\Type\Upload;

class UploadFactory
{
    public function __construct(
        private PropertyParseService $propertyParseService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createUploadFromElement(NodeElementInterface|RelationElementInterface $element): UploadInterface
    {
        if (!($element instanceof NodeElementInterface)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload element must be a node, not a relation.');
        }

        $label = $element->getLabel();
        if ('Upload' !== $label) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Can not cast element of type %s to upload.', $label ?? '<null>'));
        }
        $id = $element->getId();
        if (null === $id) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects element id to not be null.');
        }

        $properties = $element->getProperties();

        return new Upload(
            $id,
            $this->propertyParseService->getUploadLengthFromProperties($properties),
            $this->propertyParseService->getUploadOffsetFromProperties($properties),
            $this->propertyParseService->getIsUploadCompleteFromProperties($properties),
            $this->propertyParseService->getUploadTargetFromProperties($properties),
            $this->propertyParseService->getAlreadyUploadedChunksFromProperties($properties),
            $this->propertyParseService->getUploadOwnerFromProperties($properties),
            $this->propertyParseService->getExtensionFromProperties($properties),
            $this->propertyParseService->getExpiresFromProperties($properties)
        );
    }

    public function markUploadAsComplete(UploadInterface $upload): UploadInterface
    {
        return new Upload(
            $upload->getId(),
            $upload->getUploadLength(),
            $upload->getUploadOffset(),
            true,
            $upload->getUploadTarget(),
            $upload->getAlreadyUploadedChunks(),
            $upload->getUploadOwner(),
            $upload->getExtension(),
            $upload->getExpires()
        );
    }

    public function addNewChunkToUpload(UploadInterface $upload, int $chunkLength): UploadInterface
    {
        return new Upload(
            $upload->getId(),
            $upload->getUploadLength(),
            $upload->getUploadOffset() + $chunkLength,
            $upload->isUploadComplete(),
            $upload->getUploadTarget(),
            $upload->getAlreadyUploadedChunks() + 1,
            $upload->getUploadOwner(),
            $upload->getExtension(),
            $upload->getExpires()
        );
    }
}
