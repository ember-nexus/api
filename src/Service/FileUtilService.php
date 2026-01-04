<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class FileUtilService
{
    public const int MAX_FILENAME_LENGTH = 255;

    public function __construct(
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function sanitizeFileName($fileName): string
    {
        return str_replace(['"', '*', '/', ':', '<', '>', '?', '\\', '|'], '', $fileName);
    }

    public function getFileName(NodeElementInterface|RelationElementInterface $element): string
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Element id can not be null.');
        }

        $extension = 'bin';
        $fileProperties = [];
        if ($element->hasProperty('file')) {
            $fileProperties = $element->getProperty('file');
            if (!is_array($fileProperties)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf("Expected property 'file' of element %s to be of type array, got %s.", $elementId->toString(), get_debug_type($fileProperties)));
            }
        }
        if (array_key_exists('extension', $fileProperties)) {
            $extension = $fileProperties[$extension];
        }

        // limit extension to max 32 characters
        $extension = $this->sanitizeFileName($extension);
        $extension = substr($extension, 0, 32);

        $name = $elementId->toString();
        if ($element->hasProperty('name')) {
            $elementName = $element->getProperty('name');
            if (is_string($elementName)) {
                $elementName = trim($elementName);
                if (strlen($elementName) > 0) {
                    $name = $elementName;
                }
            }
        }

        $name = $this->sanitizeFileName($name);
        $name = substr($name, 0, self::MAX_FILENAME_LENGTH - strlen($name) - 1);

        return sprintf('%s.%s', $name, $extension);
    }
}
