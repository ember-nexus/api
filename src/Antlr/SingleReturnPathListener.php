<?php

declare(strict_types=1);

namespace App\Antlr;

use Antlr\Antlr4\Runtime\ParserRuleContext;
use Antlr\Antlr4\Runtime\Tree\ErrorNode;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;
use App\Contract\ParseTreeListenerInterface;
use Context\EscapedSymbolicNameStringContext;
use Context\IndexPostfixContext;
use Context\PropertiesContext;
use Context\PropertyKeyNameContext;
use Context\ReturnClauseContext;
use Context\ReturnItemsContext;
use Context\UnescapedLabelSymbolicNameStringContext;
use Exception;
use LogicException;

class SingleReturnPathListener implements ParseTreeListenerInterface
{
    /**
     * Property names (besides everything starting with `_`) which can not be used in a search query: the conditions
     * are evaluated on every node before inaccessible results are removed, so they would leak secrets character by
     * character.
     */
    public const array FORBIDDEN_PROPERTY_NAMES = ['hash'];

    private int $numberOfReturnClauses = 0;

    /**
     * Character range (inclusive) of the return item `path`, if it can be replaced by its ids.
     *
     * @var array{start: int, stop: int}|null
     */
    private ?array $idsRewriteRange = null;

    /**
     * Range of the plain return item `path` in the query, null if the item can't safely be replaced (e.g. DISTINCT).
     *
     * @return array{start: int, stop: int}|null
     */
    public function getIdsRewriteRange(): ?array
    {
        return $this->idsRewriteRange;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function visitTerminal(TerminalNode $node): void
    {
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     *
     * @codeCoverageIgnore
     */
    public function visitErrorNode(ErrorNode $node): void
    {
    }

    public function enterEveryRule(ParserRuleContext $ctx): void
    {
        $this->assertNoForbiddenConstruct($ctx);
        if ($ctx instanceof ReturnClauseContext) {
            ++$this->numberOfReturnClauses;
            if ($this->numberOfReturnClauses > 1) {
                throw new Exception("Path query requires exactly one return clause, multiple provided. Only use 'RETURN path'."); // @codeCoverageIgnore
            }

            $currentChild = null;
            foreach ($ctx->returnBody()->children ?? [] as $child) {
                if ($child instanceof ReturnItemsContext) {
                    $currentChild = $child;
                    break;
                }
            }
            if (null === $currentChild) {
                throw new LogicException("Unable to find mandatory return item element, use 'RETURN path'."); // @codeCoverageIgnore
            }
            $this->idsRewriteRange = $this->findIdsRewriteRange($ctx, $currentChild);
            while ($currentChild) {
                if ($currentChild instanceof UnescapedLabelSymbolicNameStringContext) {
                    if ('path' !== $currentChild->getText()) {
                        throw new Exception("Path query is not allowed to return variables other than 'path', use 'RETURN path'.");
                    }
                    break;
                }
                if ($currentChild instanceof EscapedSymbolicNameStringContext) {
                    if ('`path`' !== $currentChild->getText()) {
                        throw new Exception("Path query is not allowed to return variables other than 'path', use 'RETURN path'.");
                    }
                    break;
                }
                if (1 !== $currentChild->getChildCount()) {
                    throw new Exception("Path query is not allowed to return multiple variables, use 'RETURN path'."); // @codeCoverageIgnore
                }
                $currentChild = $currentChild->getChild(0);
            }
        }
    }

    private function assertNoForbiddenConstruct(ParserRuleContext $ctx): void
    {
        if ($ctx instanceof PropertyKeyNameContext) {
            $this->assertPropertyNameAllowed($ctx->getText());

            return;
        }
        if ($ctx instanceof IndexPostfixContext) {
            // `n[$name]` / `n["_passwordHash"]` read properties by a computed name, which can't be checked
            if (1 !== preg_match('/^[0-9]+$/', (string) $ctx->expression()?->getText())) {
                throw new Exception('Path query is not allowed to use dynamic property access; index with an integer literal only.');
            }

            return;
        }
        if ($ctx instanceof PropertiesContext) {
            if (null !== $ctx->parameter()) {
                // `(n $properties)` matches by properties which are only known at runtime
                throw new Exception('Path query is not allowed to use a parameter as property map; list the properties instead.');
            }

            return;
        }
    }

    private function assertPropertyNameAllowed(string $rawName): void
    {
        $name = $rawName;
        if (str_starts_with($name, '`')) {
            if (str_contains($name, '\\')) {
                throw new Exception('Path query is not allowed to use escape sequences in property names.');
            }
            $name = str_replace('``', '`', substr($name, 1, -1));
        }
        if (str_starts_with($name, '_') || in_array(strtolower($name), self::FORBIDDEN_PROPERTY_NAMES, true)) {
            throw new Exception(sprintf("Path query is not allowed to use the internal property '%s'.", $name));
        }
    }

    /**
     * @return array{start: int, stop: int}|null
     */
    private function findIdsRewriteRange(ReturnClauseContext $ctx, ReturnItemsContext $returnItems): ?array
    {
        foreach ($ctx->returnBody()->children ?? [] as $child) {
            // with DISTINCT, ORDER BY can only use projected values, which the replacement would change
            if ($child instanceof TerminalNode && 'DISTINCT' === strtoupper((string) $child->getText())) {
                return null;
            }
        }
        $start = $returnItems->getStart();
        $stop = $returnItems->getStop();
        if (null === $start || null === $stop || !in_array($returnItems->getText(), ['path', '`path`'], true)) {
            return null;
        }

        return ['start' => $start->getStartIndex(), 'stop' => $stop->getStopIndex()];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function exitEveryRule(ParserRuleContext $ctx): void
    {
    }

    public function exitTree(): void
    {
        if (1 !== $this->numberOfReturnClauses) {
            throw new Exception("Path query requires exactly one return clause. Add 'RETURN path'."); // @codeCoverageIgnore
        }
    }
}
