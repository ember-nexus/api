<?php

declare(strict_types=1);

namespace App\Exception;

use App\Type\InvalidUuidReason;
use InvalidArgumentException;

class InvalidUuidException extends InvalidArgumentException
{
    public function __construct(
        private readonly InvalidUuidReason $reason,
        string $message,
    ) {
        parent::__construct(sprintf('%s: %s', $reason->value, $message));
    }

    public function getReason(): InvalidUuidReason
    {
        return $this->reason;
    }
}
