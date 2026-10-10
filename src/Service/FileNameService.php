<?php

declare(strict_types=1);

namespace App\Service;

class FileNameService
{
    // there is no universal hard limit on filename length, but 255 bytes is the safe upper bound most filesystems
    // and clients support
    public const int MAX_FILENAME_LENGTH = 255;
    public const int MAX_EXTENSION_LENGTH = 64;
    public const string DEFAULT_EXTENSION = 'bin';

    /**
     * @var list<string>
     */
    private const array RESERVED_FILE_NAME_CHARACTERS = ['"', '*', '/', ':', '<', '>', '?', '\\', '|'];

    public function __construct(
        private StringService $stringService,
    ) {
    }

    public function getAsciiSafeFileName(string $fileName): string
    {
        $fileName = $this->stringService->getAsciiSafeString($fileName);
        $fileName = $this->removeReservedCharactersFromFileName($fileName);
        // Symfony rejects '%' in Content-Disposition filename fallbacks
        $fileName = str_replace('%', '', $fileName);

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
            self::RESERVED_FILE_NAME_CHARACTERS,
            '',
            $fileName
        ));
    }

    /**
     * An empty extension means that the file has no extension at all: the name is returned as it is, without a trailing dot.
     */
    public function buildFileNameFromParts(string $name, string $extension): string
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $extension = substr(\Safe\preg_replace('/\s+/', '', $extension), 0, self::MAX_EXTENSION_LENGTH);
        if ('' === $extension) {
            return trim(substr(trim($name), 0, self::MAX_FILENAME_LENGTH));
        }
        $name = trim(substr(trim($name), 0, self::MAX_FILENAME_LENGTH - strlen($extension) - 1));

        return sprintf('%s.%s', $name, $extension);
    }
}
