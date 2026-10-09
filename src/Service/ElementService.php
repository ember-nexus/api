<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use Ramsey\Uuid\UuidInterface;

class ElementService
{
    public function __construct(
        private FileNameService $fileNameService,
        private FilePropertyService $filePropertyService,
        private StorageService $storageService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * Key of the stored file of the element in the storage bucket, or null if the element has no file. The `hasFile`
     * check has to stay here and short-circuit before the id/extension lookups: an element without `hasFile` may
     * also have no id yet, and `getElementId()` would throw on it instead of yielding null.
     */
    public function getStorageKeyOfFile(NodeElementInterface|RelationElementInterface $element): ?string
    {
        if (!$this->hasFile($element)) {
            return null;
        }

        return $this->storageService->getStorageBucketKey($this->getElementId($element), $this->getFileNameExtension($element));
    }

    public function getElementId(NodeElementInterface|RelationElementInterface $element): UuidInterface
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Expected element.id to not be null.');
        }

        return $elementId;
    }

    /**
     * Uses the 'hasFile' flag, which is managed by the file endpoints and always kept in sync with the file.
     */
    public function hasFile(NodeElementInterface|RelationElementInterface $element): bool
    {
        return $element->hasProperty('hasFile') && true === $element->getProperty('hasFile');
    }

    public function getFileName(NodeElementInterface|RelationElementInterface $element): string
    {
        $base = $this->getFileNameBase($element);
        $extension = $this->getFileNameExtension($element);

        return $this->fileNameService->buildFileNameFromParts($base, $extension);
    }

    protected function getFileNameBase(
        NodeElementInterface|RelationElementInterface $element,
    ): string {
        $elementId = $this->getElementId($element);
        $fallbackName = $elementId->toString();

        // try to use the content of the 'name' property as the file's name
        if (!$element->hasProperty('name')) {
            return $fallbackName;
        }
        $nameProperty = $element->getProperty('name');
        if (!is_string($nameProperty)) {
            return $fallbackName;
        }
        $nameProperty = $this->fileNameService->removeReservedCharactersFromFileName($nameProperty);
        if (0 === strlen($nameProperty)) {
            return $fallbackName;
        }

        return $nameProperty;
    }

    public function getFileNameExtension(
        NodeElementInterface|RelationElementInterface $element,
    ): string {
        $parsedFileProperty = $this->filePropertyService->parseFilePropertyFromElement($element);

        return $parsedFileProperty?->getExtension() ?? FileNameService::DEFAULT_EXTENSION;
    }
}
