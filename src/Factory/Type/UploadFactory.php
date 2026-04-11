<?php

declare(strict_types=1);

namespace App\Factory\Type;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\FileService;
use App\Type\Upload;
use DateTime;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UploadFactory
{
    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createUploadFromElement(NodeElementInterface|RelationElementInterface $element): Upload
    {
        if (!($element instanceof NodeElementInterface)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload element must be a node, not a relation.');
        }

        $label = $element->getLabel() ?? 'no-label';
        if ('Upload' !== $label) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Can not cast element of type %s to upload.', $label));
        }
        $id = $element->getId();
        if (null === $id) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects element id to not be null.');
        }

        $properties = $element->getProperties();

        return new Upload(
            $id,
            $this->getUploadLengthFromProperties($properties),
            $this->getUploadOffsetFromProperties($properties),
            $this->getIsUploadCompleteFromProperties($properties),
            $this->getUploadTargetFromProperties($properties),
            $this->getAlreadyUploadedChunksFromProperties($properties),
            $this->getUploadOwnerFromProperties($properties),
            $this->getExtensionFromProperties($properties),
            $this->getMimeTypeFromProperties($properties),
            $this->getExpiresFromProperties($properties)
        );
    }

    public function markUploadAsComplete(Upload $upload): Upload
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
            $upload->getMimeType(),
            $upload->getExpires()
        );
    }

    public function addNewChunkToUpload(Upload $upload, int $chunkLength): Upload
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
            $upload->getMimeType(),
            $upload->getExpires()
        );
    }

    /**
     * @param mixed[] $properties
     */
    private function getUploadLengthFromProperties(mixed $properties): ?int
    {
        $uploadLength = null;
        if (array_key_exists('uploadLength', $properties)) {
            $uploadLength = $properties['uploadLength'];
            if (!is_int($uploadLength)) {
                throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property uploadLength to be either int or to be absent.');
            }
        }

        return $uploadLength;
    }

    /**
     * @param mixed[] $properties
     */
    private function getUploadOffsetFromProperties(mixed $properties): int
    {
        if (!array_key_exists('uploadOffset', $properties)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property uploadOffset to be present.');
        }
        $uploadOffset = $properties['uploadOffset'];
        if (!is_int($uploadOffset)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property uploadOffset to be int.');
        }
        if ($uploadOffset < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property uploadOffset to be positive int.');
        }

        return $uploadOffset;
    }

    /**
     * @param mixed[] $properties
     */
    private function getIsUploadCompleteFromProperties(mixed $properties): bool
    {
        if (!array_key_exists('uploadComplete', $properties)) {
            return false;
        }
        $uploadComplete = $properties['uploadComplete'];
        if (!is_bool($uploadComplete)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property uploadComplete to be bool.');
        }

        return $uploadComplete;
    }

    /**
     * @param mixed[] $properties
     */
    private function getUploadTargetFromProperties(mixed $properties): UuidInterface
    {
        if (!array_key_exists('uploadTarget', $properties)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property uploadTarget to be present.');
        }
        $uploadTarget = $properties['uploadTarget'];
        if (is_string($uploadTarget)) {
            $uploadTarget = Uuid::fromString($uploadTarget);
        }
        if (!($uploadTarget instanceof UuidInterface)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Upload expects property uploadTarget to be a uuid, got %s.', get_debug_type($uploadTarget)));
        }

        return $uploadTarget;
    }

    /**
     * @param mixed[] $properties
     */
    private function getAlreadyUploadedChunksFromProperties(mixed $properties): int
    {
        if (!array_key_exists('alreadyUploadedChunks', $properties)) {
            return 0;
        }
        $alreadyUploadedChunks = $properties['alreadyUploadedChunks'];
        if (!is_int($alreadyUploadedChunks)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property alreadyUploadedChunks to be int.');
        }
        if ($alreadyUploadedChunks < 0) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property alreadyUploadedChunks to be positive int.');
        }

        return $alreadyUploadedChunks;
    }

    /**
     * @param mixed[] $properties
     */
    private function getUploadOwnerFromProperties(mixed $properties): UuidInterface
    {
        if (!array_key_exists('uploadOwner', $properties)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property uploadOwner to be present.');
        }
        $uploadOwner = $properties['uploadOwner'];
        if (is_string($uploadOwner)) {
            $uploadOwner = Uuid::fromString($uploadOwner);
        }
        if (!($uploadOwner instanceof UuidInterface)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Upload expects property uploadOwner to be a uuid, got %s.', get_debug_type($uploadOwner)));
        }

        return $uploadOwner;
    }

    /**
     * @param mixed[] $properties
     */
    private function getExtensionFromProperties(mixed $properties): string
    {
        if (!array_key_exists('extension', $properties)) {
            return FileService::DEFAULT_EXTENSION;
        }
        $extension = $properties['extension'];
        if (!is_string($extension)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property extension to be string.');
        }

        return $extension;
    }

    /**
     * @param mixed[] $properties
     */
    private function getMimeTypeFromProperties(mixed $properties): string
    {
        if (!array_key_exists('mimeType', $properties)) {
            return FileService::DEFAULT_MIME_TYPE;
        }
        $mimeType = $properties['mimeType'];
        if (!is_string($mimeType)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property mimeType to be string.');
        }

        return $mimeType;
    }

    /**
     * @param mixed[] $properties
     */
    private function getExpiresFromProperties(mixed $properties): DateTime
    {
        if (!array_key_exists('expires', $properties)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property expires to be present.');
        }
        $expires = $properties['expires'];
        if ($expires instanceof DateTimeImmutable) {
            $expires = DateTime::createFromImmutable($expires);
        }
        if (!($expires instanceof DateTime)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Upload expects property expires to be a DateTime, got %s.', get_debug_type($expires)));
        }

        return $expires;
    }
}
