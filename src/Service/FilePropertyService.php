<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Type\FileProperty;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Emoji\EmojiTransliterator;
use Transliterator;

class FilePropertyService
{

    public function __construct(
        private ElementService $elementService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function parseFilePropertyFromElement(NodeElementInterface|RelationElementInterface $element): ?FileProperty
    {
        $elementId = $this->elementService->getElementId($element);
        $parsedFileProperty = new FileProperty();

        if (!$element->hasProperty('file')) {
            return null;
        }

        $rawFileProperties = $element->getProperty('file');
        if (!is_array($rawFileProperties)) {
            // todo: make sure that the property 'file' is restricted, i.e. users can not directly change these properties
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf("Expected property 'file' of element %s to be of type array, got %s.", $elementId->toString(), get_debug_type($rawFileProperties)));
        }

        $parsedFileProperty->setExtension($this->parseExtensionPropertyFromRawFileProperties($rawFileProperties, $elementId));

        return $parsedFileProperty;
    }

    protected function parseExtensionPropertyFromRawFileProperties(array $rawFileProperties, UuidInterface $elementId): string
    {
        if (!array_key_exists('extension', $rawFileProperties)) {
            return FileService::DEFAULT_EXTENSION;
        }
        $extensionProperty = $rawFileProperties['extension'];
        if (!is_string($extensionProperty)){
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf("Expected property 'file.extension' of element %s to be of type string, got %s.", $elementId->toString(), get_debug_type($extensionProperty)));
        }
        if (0 === strlen($extensionProperty)) {
            return FileService::DEFAULT_EXTENSION;
        }

        return $extensionProperty;
    }
}
