<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Type\DashJoinedKey;
use App\Type\DottedName;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\UuidInterface;

class StorageService
{
    public const string UPLOAD_EXTENSION = 'wip';

    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * Appends the extension to a path or key; an empty extension means that the file has no extension at all, so no
     * trailing dot is added.
     */
    public function appendExtension(string $pathWithoutExtension, string $extension): string
    {
        return (string) new DottedName($pathWithoutExtension, $extension);
    }

    public function getStorageBucketKey(UuidInterface $id, string $extension): string
    {
        return $this->appendExtension(
            $this->uuidToNestedFolderStructure(
                $id,
                $this->emberNexusConfiguration->getFileS3StorageBucketLevels(),
                $this->emberNexusConfiguration->getFileS3StorageBucketLevelLength(),
            ),
            $extension
        );
    }

    /**
     * @param string|null $chunkId null for uploads which consist of a single object and have no chunk attempts
     */
    public function getUploadBucketKey(UuidInterface $id, int $chunkIndex, ?string $chunkId = null): string
    {
        if (null !== $chunkId && 1 !== \Safe\preg_match('/^[0-9A-Za-z]{1,64}$/', $chunkId)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Chunk id has to be an alphanumeric string with at most 64 characters.');
        }
        $digits = $this->emberNexusConfiguration->getFileUploadChunkDigitsLength();
        if ($chunkIndex < 0) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Chunk index can not be less than 0.');
        }
        $maxIndex = (10 ** $digits) - 1;
        if ($chunkIndex > $maxIndex) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Chunk index can not be longer than %d digits, i.e. bigger than %d.', $digits, $maxIndex));
        }

        return (string) new DottedName(
            (string) new DashJoinedKey(
                $this->uuidToNestedFolderStructure(
                    $id,
                    $this->emberNexusConfiguration->getFileS3UploadBucketLevels(),
                    $this->emberNexusConfiguration->getFileS3UploadBucketLevelLength(),
                ),
                str_pad((string) $chunkIndex, $digits, '0', STR_PAD_LEFT),
                $chunkId,
            ),
            self::UPLOAD_EXTENSION
        );
    }

    public function uuidToNestedFolderStructure(UuidInterface $uuid, int $levels = 0, int $levelLength = 2): string
    {
        $uuidAsHexString = $uuid->getHex()->toString();
        if ($levels < 0) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to generate nested folder structure from uuid with negative level argument.', ['levels' => $levels]);
        }
        if ($levelLength < 1) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to generate nested folder structure from uuid with level length less than 1.', ['levelLength' => $levelLength]);
        }
        if ($levels * $levelLength >= strlen($uuidAsHexString)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to generate nested folder structure as long as product of levels and level length exceeds length of uuid without dashes.', ['levels' => $levels, 'levelLength' => $levelLength, 'product' => $levels * $levelLength, 'limit' => strlen($uuidAsHexString) - 1]);
        }

        $parts = [];
        for ($i = 0; $i < $levels; ++$i) {
            $parts[] = substr($uuidAsHexString, $levelLength * $i, $levelLength);
        }

        return sprintf(
            '%s%s%s',
            implode('/', $parts),
            $levels > 0 ? '/' : '',
            $uuid->toString()
        );
    }
}
