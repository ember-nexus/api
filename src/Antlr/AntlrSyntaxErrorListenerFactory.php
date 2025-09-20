<?php

declare(strict_types=1);

namespace App\Antlr;

use Antlr\Antlr4\Runtime\Error\Listeners\BaseErrorListener;
use App\Factory\Exception\Client400BadGrammarExceptionFactory;

class AntlrSyntaxErrorListenerFactory extends BaseErrorListener
{
    public function __construct(
        private Client400BadGrammarExceptionFactory $client400BadGrammarExceptionFactory,
    ) {
    }

    public function createNewAntlrSyntaxErrorListener(): AntlrSyntaxErrorListener
    {
        return new AntlrSyntaxErrorListener($this->client400BadGrammarExceptionFactory);
    }
}
