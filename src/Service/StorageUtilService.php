<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Server500LogicExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\UuidInterface;

class StorageUtilService
{
    public const int STREAM_CHUNK_SIZE = 8192;
    //    public const int STREAM_CHUNK_SIZE = 1024 * 1024;

    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function getStorageBucketKey(UuidInterface $id): string
    {
        return sprintf(
            '%s.bin',
            $this->uuidToNestedFolderStructure(
                $id,
                $this->emberNexusConfiguration->getFileS3StorageBucketLevels(),
                $this->emberNexusConfiguration->getFileS3StorageBucketLevelLength(),
            )
        );
    }

    public function getUploadBucketKey(UuidInterface $id, int $index = 0): string
    {
        $digits = $this->emberNexusConfiguration->getFileUploadChunkDigitsLength();
        if ($index < 0) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Chunk index can not be less than 0.');
        }
        $maxIndex = (10 ** $digits) - 1;
        if ($index > $maxIndex) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Chunk index can not be longer than %d digits, i.e. bigger than %d.', $digits, $maxIndex));
        }

        return sprintf(
            '%s-%s.bin',
            $this->uuidToNestedFolderStructure(
                $id,
                $this->emberNexusConfiguration->getFileS3UploadBucketLevels(),
                $this->emberNexusConfiguration->getFileS3UploadBucketLevelLength(),
            ),
            str_pad((string) $index, $digits, '0', STR_PAD_LEFT),
        );
    }

    public function uuidToNestedFolderStructure(UuidInterface $uuid, int $levels = 0, int $levelLength = 2): string
    {
        $uuidAsHexString = $uuid->getHex()->toString();
        if ($levels < 0) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to generate nested folder structure from uuid with negative level argument.', ['levels' => $levels]);
        }
        if ($levelLength < 1) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to generate nested folder structure from uuid with level length less than 1.', ['levelLength' => $levelLength]);
        }
        if ($levels * $levelLength >= strlen($uuidAsHexString)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to generate nested folder structure as long as product of levels and level length exceeds length of uuid without dashes.', ['levels' => $levels, 'levelLength' => $levelLength, 'product' => $levels * $levelLength, 'limit' => strlen($uuidAsHexString) - 1]);
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
