<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Safe\DateTime;
use Safe\DateTimeImmutable;

class PropertyParseService
{
    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    /**
     * @param mixed[] $properties
     */
    public function getUploadLengthFromProperties(mixed $properties): ?int
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
    public function getUploadOffsetFromProperties(mixed $properties): int
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
    public function getIsUploadCompleteFromProperties(mixed $properties): bool
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
    public function getUploadTargetFromProperties(mixed $properties): UuidInterface
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
    public function getAlreadyUploadedChunksFromProperties(mixed $properties): int
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
    public function getUploadOwnerFromProperties(mixed $properties): UuidInterface
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
    public function getExtensionFromProperties(mixed $properties): string
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
    public function getHashStateFromProperties(mixed $properties): ?string
    {
        if (!array_key_exists('hashState', $properties)) {
            return null;
        }
        $hashState = $properties['hashState'];
        if (!is_string($hashState)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property hashState to be either string or to be absent.');
        }

        return $hashState;
    }

    /**
     * @param mixed[] $properties
     */
    public function getExpiresFromProperties(mixed $properties): DateTime
    {
        if (!array_key_exists('expires', $properties)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property expires to be present.');
        }
        $expires = $properties['expires'];
        if ($expires instanceof DateTimeImmutable || $expires instanceof \DateTimeImmutable) {
            $expires = DateTime::createFromImmutable($expires);
        }
        if (!($expires instanceof DateTime)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Upload expects property expires to be a DateTime, got %s.', get_debug_type($expires)));
        }

        return $expires;
    }
}
