<?php

declare(strict_types=1);

namespace App\Contract;

use Antlr\Antlr4\Runtime\Tree\ParseTreeListener as AntlrParseTreeListener;

interface ParseTreeListenerInterface extends AntlrParseTreeListener
{
    public function exitTree(): void;
}
