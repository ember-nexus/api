<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Type\FileProperty;
use Ramsey\Uuid\UuidInterface;

class ElementService
{
    public function __construct(
        private FileService $fileService,
        private FilePropertyService $filePropertyService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function getElementId(NodeElementInterface|RelationElementInterface $element): UuidInterface
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Expected element.id to not be null.');
        }

        return $elementId;
    }

    public function getFileName(NodeElementInterface|RelationElementInterface $element): string
    {
        $base = $this->getFileNameBase($element);
        $extension = $this->getFileNameExtension($element);

        return $this->fileService->buildFileNameFromParts($base, $extension);
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
        $nameProperty = $this->fileService->removeReservedCharactersFromFileName($nameProperty);
        if (0 === strlen($nameProperty)) {
            return $fallbackName;
        }

        return $nameProperty;
    }

    public function getFileNameExtension(
        NodeElementInterface|RelationElementInterface $element,
    ): string {
        $parsedFileProperty = $this->filePropertyService->parseFilePropertyFromElement($element);
        return $parsedFileProperty->getExtension();
    }
}
