<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client416RangeNotSatisfiableException extends ProblemJsonException
{
    /**
     * @param array<string, mixed> $additionalDetails
     */
    public function __construct(
        string $type,
        string $title = 'Range Not Satisfiable',
        int $status = 416,
        string $detail = 'The requested range can not be satisfied for the target resource.',
        ?string $instance = null,
        ?Throwable $previous = null,
        array $additionalDetails = [],
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous, $additionalDetails);
    }
}
