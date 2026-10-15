<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;

use function Safe\fflush;

/**
 * Identical to Monolog's regular stream handler, but explicitly flushes the underlying stream after every write.
 *
 * The audit channel is read back from disk by a separate process (end-to-end tests, log shippers) immediately
 * after a request completes. PHP's userland stream buffer for a plain file is not flushed until it fills up or
 * the stream closes, so without this, a record can sit invisibly in that buffer for an arbitrary amount of
 * time - especially under FrankenPHP's worker mode, where the PHP process (and therefore the open stream) is
 * reused across many requests instead of being torn down after each one.
 */
class FlushingStreamHandler extends StreamHandler
{
    protected function write(LogRecord $record): void
    {
        parent::write($record);
        if (null !== $this->stream) {
            fflush($this->stream);
        }
    }
}
