<?php

declare(strict_types=1);

namespace App\Antlr;

use Antlr\Antlr4\Runtime\ParserRuleContext;
use Antlr\Antlr4\Runtime\Tree\ErrorNode;
use Antlr\Antlr4\Runtime\Tree\ParseTree;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;
use App\Contract\ParseTreeListenerInterface;
use Context\AnyLabelContext;
use Context\AnyLabelIsContext;
use Context\EscapedSymbolicNameStringContext;
use Context\Expression2Context;
use Context\IndexPostfixContext;
use Context\LabelExpressionContext;
use Context\LabelNameContext;
use Context\LabelNameIsContext;
use Context\MapContext;
use Context\NodePatternContext;
use Context\PropertiesContext;
use Context\PropertyContext;
use Context\PropertyKeyNameContext;
use Context\PropertyPostfixContext;
use Context\RelationshipPatternContext;
use Context\ReturnClauseContext;
use Context\ReturnItemsContext;
use Context\UnescapedLabelSymbolicNameStringContext;
use Exception;
use LogicException;

use function Safe\preg_match;

class SingleReturnPathListener implements ParseTreeListenerInterface
{
    /**
     * Property names (besides everything starting with `_`) which can not be used on token nodes in a search query:
     * the conditions are evaluated on every node before inaccessible results are removed, so they would leak secrets
     * character by character.
     *
     * The names are only forbidden on elements which might be tokens, i.e. nodes with the label `Token` in their label
     * expression and nodes without a label expression or with a wildcard or negated one. Relations and nodes which
     * are bound to other labels can use them, e.g. a `Data` node with its own `hash` property.
     */
    public const array FORBIDDEN_PROPERTY_NAMES = ['hash'];

    public const string TOKEN_LABEL = 'Token';

    /** @var array<string, bool> variable name => true if it is guaranteed to be a non-token node or a relation */
    private array $safeVariables = [];

    /** @var array<string, true> variable names which are bound in at least one pattern which might match tokens */
    private array $unsafeVariables = [];

    private string $forbiddenPropertyName = 'hash';

    /** @var list<string|null> variable names (null: no plain variable) of property accesses to forbidden names */
    private array $forbiddenPropertyAccesses = [];

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
        if ($ctx instanceof NodePatternContext || $ctx instanceof RelationshipPatternContext) {
            $this->registerPatternVariable($ctx);

            return;
        }
        if ($ctx instanceof PropertyKeyNameContext) {
            $this->assertPropertyNameAllowed($ctx);

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

    private function assertPropertyNameAllowed(PropertyKeyNameContext $ctx): void
    {
        $name = $ctx->getText();
        if (str_starts_with($name, '`')) {
            if (str_contains($name, '\\')) {
                throw new Exception('Path query is not allowed to use escape sequences in property names.');
            }
            $name = str_replace('``', '`', substr($name, 1, -1));
        }
        if (str_starts_with($name, '_')) {
            throw new Exception(sprintf("Path query is not allowed to use the internal property '%s'.", $name));
        }
        if (!in_array(strtolower($name), self::FORBIDDEN_PROPERTY_NAMES, true)) {
            return;
        }
        $this->forbiddenPropertyName = $name;

        if ($this->registerForbiddenPropertyAccess($ctx)) {
            return;
        }
        if (!$this->isAllowedInPatternProperties($ctx)) {
            throw new Exception(sprintf("Path query is not allowed to use the internal property '%s'.", $name));
        }
    }

    /**
     * Remembers `variable.name` accesses, which are checked once all variables are known. Chained or computed bases like
     * `(n).name` can't be classified and are remembered without variable.
     *
     * @return bool true if the name is used as property access
     */
    private function registerForbiddenPropertyAccess(PropertyKeyNameContext $ctx): bool
    {
        $property = $ctx->getParent();
        $postfix = $property instanceof PropertyContext ? $property->getParent() : null;
        $expression = $postfix instanceof PropertyPostfixContext ? $postfix->getParent() : null;
        if (!$expression instanceof Expression2Context) {
            return false;
        }
        $base = $expression->expression1()?->getText();
        $isFirstPostfix = $expression->postFix(0) === $postfix;
        $this->forbiddenPropertyAccesses[] = ($isFirstPostfix && null !== $base) ? $this->normalizeVariable($base) : null;

        return true;
    }

    /**
     * `(n:Label {name: 1})` is fine for relations and for nodes which can't be tokens.
     */
    private function isAllowedInPatternProperties(PropertyKeyNameContext $ctx): bool
    {
        $map = $ctx->getParent();
        $properties = $map?->getParent();
        $pattern = $properties instanceof PropertiesContext ? $properties->getParent() : null;
        if (!$map instanceof MapContext) {
            return false;
        }

        return $pattern instanceof RelationshipPatternContext
            || ($pattern instanceof NodePatternContext && $this->isTokenSafe($pattern->labelExpression()));
    }

    private function registerPatternVariable(ParserRuleContext $ctx): void
    {
        $variable = $ctx instanceof NodePatternContext ? $ctx->variable() : ($ctx instanceof RelationshipPatternContext ? $ctx->variable() : null);
        if (null === $variable) {
            return;
        }
        $name = $this->normalizeVariable($variable->getText());
        if ($ctx instanceof RelationshipPatternContext) {
            $this->safeVariables[$name] = true;

            return;
        }
        /** @var NodePatternContext $ctx */
        $labelExpression = $ctx->labelExpression();
        if (null === $labelExpression) {
            // re-using an already bound variable does not change what it is
            return;
        }
        if ($this->isTokenSafe($labelExpression)) {
            $this->safeVariables[$name] = true;

            return;
        }
        $this->unsafeVariables[$name] = true;
    }

    /**
     * A label expression is safe if it can only match nodes which are not tokens: it has to list labels, must not
     * contain the token label and must not contain wildcards or negations (which match tokens).
     */
    private function isTokenSafe(?LabelExpressionContext $labelExpression): bool
    {
        if (null === $labelExpression) {
            return false;
        }

        return $this->labelsAreTokenSafe($labelExpression);
    }

    private function labelsAreTokenSafe(ParseTree $node): bool
    {
        if ($node instanceof AnyLabelContext || $node instanceof AnyLabelIsContext) {
            return false;
        }
        if ($node instanceof TerminalNode && '!' === $node->getText()) {
            return false;
        }
        if ($node instanceof LabelNameContext || $node instanceof LabelNameIsContext) {
            return self::TOKEN_LABEL !== $this->normalizeVariable($node->getText());
        }
        for ($i = 0; $i < $node->getChildCount(); ++$i) {
            $child = $node->getChild($i);
            if (null !== $child && !$this->labelsAreTokenSafe($child)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeVariable(string $text): string
    {
        if (str_starts_with($text, '`') && str_ends_with($text, '`') && strlen($text) >= 2) {
            return str_replace('``', '`', substr($text, 1, -1));
        }

        return $text;
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
        // property accesses are checked after the whole query is known, as variables can be bound after their first use
        foreach ($this->forbiddenPropertyAccesses as $variable) {
            if (null === $variable || array_key_exists($variable, $this->unsafeVariables) || !array_key_exists($variable, $this->safeVariables)) {
                throw new Exception(sprintf("Path query is not allowed to use the internal property '%s'.", $this->forbiddenPropertyName));
            }
        }
    }
}
