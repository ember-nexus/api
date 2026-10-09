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
     * Absent on an upload created before this property existed: defaults to `true` so the `hasFile` conflict checks
     * are skipped rather than risk rejecting a legitimate, already-in-progress replace.
     *
     * @param mixed[] $properties
     */
    public function getTargetHadFileAtCreationFromProperties(mixed $properties): bool
    {
        if (!array_key_exists('targetHadFileAtCreation', $properties)) {
            return true;
        }
        $targetHadFileAtCreation = $properties['targetHadFileAtCreation'];
        if (!is_bool($targetHadFileAtCreation)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property targetHadFileAtCreation to be bool.');
        }

        return $targetHadFileAtCreation;
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
     *
     * @return list<string>
     */
    public function getChunkIdsFromProperties(mixed $properties): array
    {
        if (!array_key_exists('chunkIds', $properties)) {
            return [];
        }
        $chunkIds = $properties['chunkIds'];
        if (!is_array($chunkIds) || !array_is_list($chunkIds)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property chunkIds to be a list of strings.');
        }
        foreach ($chunkIds as $chunkId) {
            if (!is_string($chunkId)) {
                throw $this->client400BadContentExceptionFactory->createFromDetail('Upload expects property chunkIds to be a list of strings.');
            }
        }

        return $chunkIds;
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
        // a missing, null or non-string extension falls back to the default; an empty string means no extension
        $extension = $properties['extension'] ?? null;
        if (!is_string($extension)) {
            return FileNameService::DEFAULT_EXTENSION;
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
