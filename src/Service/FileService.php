<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Emoji\EmojiTransliterator;
use Transliterator;

class FileService
{
    public const int MAX_FILENAME_LENGTH = 255;
    public const int MAX_EXTENSION_LENGTH = 16;
    public const string DEFAULT_EXTENSION = 'bin';
    public const string UPLOAD_EXTENSION = 'wip';

    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private StringService $stringService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function getAsciiSafeFileName(string $fileName): string
    {
        $fileName = $this->stringService->getAsciiSafeString($fileName);
        $fileName = $this->removeReservedCharactersFromFileName($fileName);

        $parts = explode('.', $fileName, 2);
        if (2 === count($parts)) {
            $baseName = $parts[0];
            $extension = trim(substr($parts[1], 0, self::MAX_EXTENSION_LENGTH));

            return sprintf(
                '%s.%s',
                trim(substr($baseName, 0, self::MAX_FILENAME_LENGTH - strlen($extension) - 1)),
                $extension
            );
        }

        return substr($fileName, 0, self::MAX_FILENAME_LENGTH);
    }

    public function removeReservedCharactersFromFileName(string $fileName): string
    {
        return trim(str_replace(
            ['"', '*', '/', ':', '<', '>', '?', '\\', '|'],
            '',
            $fileName
        ));
    }

    public function buildFileNameFromParts(string $name, string $extension): string
    {
        $extension = trim(substr(trim($extension), 0, self::MAX_EXTENSION_LENGTH));
        $name = trim(substr(trim($name), 0, self::MAX_FILENAME_LENGTH - strlen($extension) - 1));

        return sprintf('%s.%s', $name, $extension);
    }

    public function getStorageBucketKey(UuidInterface $id, string $extension): string
    {
        return sprintf(
            '%s.%s',
            $this->uuidToNestedFolderStructure(
                $id,
                $this->emberNexusConfiguration->getFileS3StorageBucketLevels(),
                $this->emberNexusConfiguration->getFileS3StorageBucketLevelLength(),
            ),
            $extension
        );
    }

    public function getUploadBucketKey(UuidInterface $id, int $chunkIndex): string
    {
        $digits = $this->emberNexusConfiguration->getFileUploadChunkDigitsLength();
        if ($chunkIndex < 0) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Chunk index can not be less than 0.');
        }
        $maxIndex = (10 ** $digits) - 1;
        if ($chunkIndex > $maxIndex) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Chunk index can not be longer than %d digits, i.e. bigger than %d.', $digits, $maxIndex));
        }

        return sprintf(
            '%s-%s.%s',
            $this->uuidToNestedFolderStructure(
                $id,
                $this->emberNexusConfiguration->getFileS3UploadBucketLevels(),
                $this->emberNexusConfiguration->getFileS3UploadBucketLevelLength(),
            ),
            str_pad((string) $chunkIndex, $digits, '0', STR_PAD_LEFT),
            self::UPLOAD_EXTENSION
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
