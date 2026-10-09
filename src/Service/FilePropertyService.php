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
        // a missing, null or non-string extension falls back to the default; an empty string is valid and means that the
        // file has no extension at all
        $extensionProperty = $rawFileProperties['extension'] ?? null;
        if (!is_string($extensionProperty)) {
            return FileNameService::DEFAULT_EXTENSION;
        }

        return $extensionProperty;
    }
}
