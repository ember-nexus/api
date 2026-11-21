<?php

declare(strict_types=1);

namespace App\Tests\CsFixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Moves all client and server exception factories (e.g. Client400BadContentExceptionFactory) to the end of the
 * constructor's parameter list, sorted alphabetically by type name. The order of all other parameters is kept.
 *
 * Risky, because callers which pass constructor arguments positionally are not adjusted.
 */
final class ConstructorExceptionFactoriesLastFixer extends AbstractFixer
{
    private const string FACTORY_PATTERN = '/^(Client|Server)\d+\w*ExceptionFactory$/';

    public function getName(): string
    {
        return 'EmberNexus/constructor_exception_factories_last';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Client and server exception factories must be the last parameters of a constructor.',
            [new CodeSample("<?php\nclass A\n{\n    public function __construct(\n        private Client400BadContentExceptionFactory \$factory,\n        private Logger \$logger,\n    ) {\n    }\n}\n")],
            null,
            'Changes the constructor signature, callers which pass arguments positionally are not adjusted.'
        );
    }

    public function getPriority(): int
    {
        // runs before the whitespace fixers, so that they can tidy up afterwards
        return 100;
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_FUNCTION, T_VARIABLE]);
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }
            $nameIndex = $tokens->getNextMeaningfulToken($index);
            if (null === $nameIndex || !$tokens[$nameIndex]->equals([T_STRING, '__construct'], false)) {
                continue;
            }
            $openIndex = $tokens->getNextMeaningfulToken($nameIndex);
            if (null === $openIndex || !$tokens[$openIndex]->equals('(')) {
                continue;
            }
            $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openIndex);
            $this->fixParameters($tokens, $openIndex, $closeIndex);
        }
    }

    private function fixParameters(Tokens $tokens, int $openIndex, int $closeIndex): void
    {
        $slots = $this->findParameterSlots($tokens, $openIndex, $closeIndex);

        $contents = [];
        $group = [];
        $factoryNames = [];
        foreach ($slots as [$start, $end]) {
            $content = [];
            for ($i = $start; $i <= $end; ++$i) {
                $content[] = clone $tokens[$i];
            }
            $contents[] = $content;
            // 0: regular, 1: exception factory, 2: has default value; required parameters must not follow optional ones
            $factoryName = $this->getFactoryName($tokens, $start, $end);
            $group[] = $this->hasDefaultValue($tokens, $start, $end) ? 2 : (null !== $factoryName ? 1 : 0);
            $factoryNames[] = $factoryName ?? '';
        }

        $order = $this->getTargetOrder($group, $factoryNames);
        if ($order === array_keys($order)) {
            return;
        }

        for ($slot = count($slots) - 1; $slot >= 0; --$slot) {
            [$start, $end] = $slots[$slot];
            $tokens->overrideRange($start, $end, $contents[$order[$slot]]);
        }
    }

    /**
     * Content range [start, end] of every parameter, separators and whitespace are not part of it.
     *
     * @return array<int, array{int, int}>
     */
    private function findParameterSlots(Tokens $tokens, int $openIndex, int $closeIndex): array
    {
        $slots = [];
        $slotStart = $openIndex + 1;
        for ($i = $openIndex + 1; $i <= $closeIndex; ++$i) {
            $blockType = Tokens::detectBlockType($tokens[$i]);
            if (null !== $blockType && $blockType['isStart'] && $i < $closeIndex) {
                $i = $tokens->findBlockEnd($blockType['type'], $i);
                continue;
            }
            if (!$tokens[$i]->equals(',') && $i !== $closeIndex) {
                continue;
            }
            $slot = $this->trimWhitespace($tokens, $slotStart, $i - 1);
            if (null !== $slot) {
                $slots[] = $slot;
            }
            $slotStart = $i + 1;
        }

        return $slots;
    }

    /**
     * @return array{int, int}|null
     */
    private function trimWhitespace(Tokens $tokens, int $start, int $end): ?array
    {
        while ($start <= $end && $tokens[$start]->isWhitespace()) {
            ++$start;
        }
        while ($end >= $start && $tokens[$end]->isWhitespace()) {
            --$end;
        }

        return $start <= $end ? [$start, $end] : null;
    }

    /**
     * @param array<int, int>    $group        0: regular, 1: exception factory, 2: has default value
     * @param array<int, string> $factoryNames
     *
     * @return array<int, int> slot index which has to be placed at each position
     */
    private function getTargetOrder(array $group, array $factoryNames): array
    {
        $order = [];
        foreach ([0, 1, 2] as $wantGroup) {
            $groupSlots = array_keys($group, $wantGroup, true);
            if (1 === $wantGroup) {
                // exception factories are sorted alphabetically by type name, the sort is stable
                usort($groupSlots, static fn (int $a, int $b): int => strcmp($factoryNames[$a], $factoryNames[$b]));
            }
            array_push($order, ...$groupSlots);
        }

        return $order;
    }

    private function hasDefaultValue(Tokens $tokens, int $start, int $end): bool
    {
        for ($i = $start; $i <= $end; ++$i) {
            $blockType = Tokens::detectBlockType($tokens[$i]);
            if (null !== $blockType && $blockType['isStart']) {
                $i = $tokens->findBlockEnd($blockType['type'], $i);
                continue;
            }
            if ($tokens[$i]->equals('=')) {
                return true;
            }
        }

        return false;
    }

    private function getFactoryName(Tokens $tokens, int $start, int $end): ?string
    {
        for ($i = $start; $i <= $end; ++$i) {
            $token = $tokens[$i];
            if ($token->isGivenKind(T_VARIABLE)) {
                return null;
            }
            if ($token->isGivenKind(T_STRING) && 1 === preg_match(self::FACTORY_PATTERN, $token->getContent())) {
                return $token->getContent();
            }
        }

        return null;
    }
}
