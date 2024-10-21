<?php

declare(strict_types=1);

namespace App\Antlr;

use Antlr\Antlr4\Runtime\Error\Exceptions\RecognitionException;
use Antlr\Antlr4\Runtime\Error\Listeners\BaseErrorListener;
use Antlr\Antlr4\Runtime\Recognizer;
use App\Factory\Exception\Client400BadGrammarExceptionFactory;

class AntlrSyntaxErrorListener extends BaseErrorListener
{
    public function __construct(
        private Client400BadGrammarExceptionFactory $client400BadGrammarExceptionFactory,
    ) {
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function syntaxError(
        Recognizer $recognizer,
        ?object $offendingSymbol,
        int $line,
        int $charPositionInLine,
        string $msg,
        ?RecognitionException $exception = null,
    ): void {
        throw $this->client400BadGrammarExceptionFactory->createFromDetail(sprintf('Syntax exception at line %d position %d: %s', $line, $charPositionInLine, $msg), $exception);
    }
}
