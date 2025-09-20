<?php

declare(strict_types=1);

namespace App\Antlr;

use Antlr\Antlr4\Runtime\ParserRuleContext;
use Antlr\Antlr4\Runtime\Tree\ErrorNode;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;
use App\Contract\ParseTreeListenerInterface;
use Context\EscapedSymbolicNameStringContext;
use Context\ReturnClauseContext;
use Context\ReturnItemsContext;
use Context\UnescapedLabelSymbolicNameStringContext;
use Exception;
use LogicException;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class SingleReturnPathListener implements ParseTreeListenerInterface
{
    private int $numberOfReturnClauses = 0;

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
