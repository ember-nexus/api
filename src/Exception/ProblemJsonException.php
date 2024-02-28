<?php

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
     * @param string         $type     A URI reference [RFC3986] that identifies the
     * @param string         $title    A short, human-readable summary of the problem
     * @param int            $status   The HTTP status code ([RFC7231], Section 6)
     * @param string         $detail   A human-readable explanation specific to this
     * @param string|null    $instance A URI reference that identifies the specific
     * @param Throwable|null $previous the previous throwable used for the exception chaining
     */
    public function __construct(
        string $type,
        string $title,
        int $status,
        string $detail,
        ?string $instance = null,
        ?Throwable $previous = null
    ) {
        parent::__construct('', 0, $previous);
        $this->type = $type;
        $this->title = $title;
        $this->status = $status;
        $this->detail = $detail;
        $this->instance = $instance;
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
}
