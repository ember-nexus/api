<?php

declare(strict_types=1);

namespace App\Exception;

use App\Contract\ExceptionInterface;
use Exception;
use Throwable;

class ProblemJsonException extends Exception implements ExceptionInterface
{
    protected string $type;
    protected string $title;
    protected int $status;
    protected string $detail;
    protected ?string $instance = null;
    /**
     * @var array<string, mixed>
     */
    protected array $additionalProperties;

    /**
     * @param string               $type                 A URI reference [RFC3986] that identifies the
     * @param string               $title                A short, human-readable summary of the problem
     * @param int                  $status               The HTTP status code ([RFC7231], Section 6)
     * @param string               $detail               A human-readable explanation specific to this
     * @param string|null          $instance             A URI reference that identifies the specific
     * @param Throwable|null       $previous             the previous throwable used for the exception chaining
     * @param array<string, mixed> $additionalProperties Array of additional properties which will be added to the resulting Problem JSON response
     */
    public function __construct(
        string $type,
        string $title,
        int $status,
        string $detail,
        ?string $instance = null,
        ?Throwable $previous = null,
        array $additionalProperties = [],
    ) {
        parent::__construct('', 0, $previous);
        $this->type = $type;
        $this->title = $title;
        $this->status = $status;
        $this->detail = $detail;
        $this->instance = $instance;
        $this->additionalProperties = $additionalProperties;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getInstance(): ?string
    {
        return $this->instance;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdditionalProperties(): array
    {
        return $this->additionalProperties;
    }
}
