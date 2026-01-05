<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;
use Symfony\Component\Emoji\EmojiTransliterator;
use Transliterator;

class FileUtilService
{
    public const int MAX_FILENAME_LENGTH = 255;
    public const int MAX_EXTENSION_LENGTH = 32;
    public const string DEFAULT_EXTENSION = 'bin';

    public function __construct(
        private ElementService $elementService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function getAsciiSafeFileName(string $fileName): string
    {
        $transliterator = EmojiTransliterator::create('en');
        $wip = $transliterator->transliterate($fileName);
        if (false === $wip) {
            $wip = $fileName; // @codeCoverageIgnore
        }

        $wip = \Safe\mb_convert_encoding($wip, 'UTF-8', 'UTF-8');
        if (!is_string($wip)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected mb_convert_encoding to return string, got %s.', get_debug_type($wip))); // @codeCoverageIgnore
        }

        $transliterator = Transliterator::create(
            'Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Latin-ASCII'
        );
        if (null === $transliterator) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to create Transliterator-object; is ext-intl installed?'); // @codeCoverageIgnore
        }
        $wip = $transliterator->transliterate($wip);
        if (!is_string($wip)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected transliterate to return string, got %s.', get_debug_type($wip))); // @codeCoverageIgnore
        }
        $wip = \Safe\preg_replace('/[^\x20-\x7E]/', '', $wip);
        /**
         * @phpstan-ignore-next-line
         */
        if (!is_string($wip)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected preg_replace to return string, got %s.', get_debug_type($wip))); // @codeCoverageIgnore
        }
        $wip = trim($wip);

        $parts = explode('.', $wip, 2);
        if (2 === count($parts)) {
            $baseName = $parts[0];
            $extension = trim(substr($parts[1], 0, self::MAX_EXTENSION_LENGTH));

            return sprintf(
                '%s.%s',
                trim(substr($baseName, 0, self::MAX_FILENAME_LENGTH - strlen($extension) - 1)),
                $extension
            );
        }

        return substr($wip, 0, self::MAX_FILENAME_LENGTH);
    }

    public function getFileName(NodeElementInterface|RelationElementInterface $element): string
    {
        $baseName = $this->getBaseNameContent($element);
        $extension = $this->getExtension($element);

        $baseName = substr($baseName, 0, self::MAX_FILENAME_LENGTH - strlen($extension) - 1);

        return sprintf('%s.%s', $baseName, $extension);
    }

    public function sanitizeString(string $value): string
    {
        return trim(str_replace(
            ['"', '*', '/', ':', '<', '>', '?', '\\', '|'],
            '',
            $value
        ));
    }

    /**
     * @note This function returns the content of an element's file name, however this content might be shortened later.
     *       Use getFileName() for a safe implementation.
     */
    public function getBaseNameContent(
        NodeElementInterface|RelationElementInterface $element,
    ): string {
        $elementId = $this->elementService->getElementId($element);
        $baseName = $elementId->toString();

        if (!$element->hasProperty('name')) {
            return $baseName;
        }
        $elementName = $element->getProperty('name');
        if (!is_string($elementName)) {
            return $baseName;
        }
        $elementName = $this->sanitizeString($elementName);
        if (0 === strlen($elementName)) {
            return $baseName;
        }

        return $elementName;
    }

    public function getExtension(
        NodeElementInterface|RelationElementInterface $element,
    ): string {
        $elementId = $this->elementService->getElementId($element);
        $extension = self::DEFAULT_EXTENSION;

        if (!$element->hasProperty('file')) {
            return $extension;
        }
        $fileProperties = $element->getProperty('file');
        if (!is_array($fileProperties)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf("Expected property 'file' of element %s to be of type array, got %s.", $elementId->toString(), get_debug_type($fileProperties)));
        }
        if (!array_key_exists('extension', $fileProperties)) {
            return $extension;
        }
        $extension = $fileProperties['extension'];
        $extension = $this->sanitizeString($extension);

        return substr($extension, 0, self::MAX_EXTENSION_LENGTH);
    }
}
