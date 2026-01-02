<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client409ConflictException extends ProblemJsonException
{
    /**
     * @param array<string, mixed> $additionalDetails
     */
    public function __construct(
        string $type,
        string $title = 'Conflict',
        int $status = 409,
        string $detail = 'Operation can not be performed due to some sort of conflict.',
        ?string $instance = null,
        ?Throwable $previous = null,
        array $additionalDetails = [],
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous, $additionalDetails);
    }
}
