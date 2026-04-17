<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Type\FileProperty;
use Ramsey\Uuid\UuidInterface;

class FilePropertyService
{
    public function __construct(
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function parseFilePropertyFromElement(NodeElementInterface|RelationElementInterface $element): ?FileProperty
    {
        $parsedFileProperty = new FileProperty();

        if (!$element->hasProperty('file')) {
            return null;
        }

        $rawFileProperties = $element->getProperty('file');
        if (!is_array($rawFileProperties)) {
            // todo: make sure that the property 'file' is restricted, i.e. users can not directly change these properties
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Expected property 'file' of element %s to be of type array, got %s.", $element->getId()?->toString() ?? 'null', get_debug_type($rawFileProperties)));
        }

        $parsedFileProperty->setExtension($this->parseExtensionPropertyFromRawFileProperties($rawFileProperties, $element->getId()));

        return $parsedFileProperty;
    }

    /**
     * @param array<string, mixed> $rawFileProperties
     */
    protected function parseExtensionPropertyFromRawFileProperties(array $rawFileProperties, ?UuidInterface $elementId): string
    {
        if (!array_key_exists('extension', $rawFileProperties)) {
            return FileService::DEFAULT_EXTENSION;
        }
        $extensionProperty = $rawFileProperties['extension'];
        if (!is_string($extensionProperty)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Expected property 'file.extension' of element %s to be of type string, got %s.", $elementId?->toString() ?? 'null', get_debug_type($extensionProperty)));
        }
        if (0 === strlen($extensionProperty)) {
            return FileService::DEFAULT_EXTENSION;
        }

        return $extensionProperty;
    }
}
