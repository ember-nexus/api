<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Server500LogicExceptionFactory;
use Symfony\Component\Emoji\EmojiTransliterator;
use Transliterator;

class StringService
{
    public function __construct(
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function enforceUtf8Encoding(string $string): string
    {
        $string = \Safe\mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        if (!is_string($string)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected mb_convert_encoding to return string, got %s.', get_debug_type($string))); // @codeCoverageIgnore
        }

        return $string;
    }

    public function removeNonAsciiCharactersFromString(string $string): string
    {
        $string = \Safe\preg_replace('/[^\x20-\x7E]/', '', $string);
        /**
         * @phpstan-ignore-next-line
         */
        if (!is_string($string)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected preg_replace to return string, got %s.', get_debug_type($string))); // @codeCoverageIgnore
        }

        return $string;
    }

    public function transliterateEmojis(string $string): string
    {
        $transliteratedString = EmojiTransliterator::create('en')->transliterate($string);

        return is_string($transliteratedString) ? $transliteratedString : $string; // @codeCoverageIgnore
    }

    public function transliterateNonLatinCharacters(string $string): string
    {
        $transliterator = Transliterator::create(
            'Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Latin-ASCII'
        );
        if (null === $transliterator) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to create Transliterator-object; is ext-intl installed?'); // @codeCoverageIgnore
        }
        $string = $transliterator->transliterate($string);
        if (!is_string($string)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected transliterate to return string, got %s.', get_debug_type($string))); // @codeCoverageIgnore
        }

        return $string;
    }

    public function getAsciiSafeString(string $string): string
    {
        $string = $this->transliterateEmojis($string);
        $string = $this->enforceUtf8Encoding($string);
        $string = $this->transliterateNonLatinCharacters($string);
        $string = $this->removeNonAsciiCharactersFromString($string);

        return trim($string);
    }
}
