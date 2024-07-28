<?php

declare(strict_types=1);

namespace App\Service;

class TusIoService
{
    private bool $isTusIoEndpoint = false;

    public function __construct(
    ) {
    }

    public function isTusIoEndpoint(): bool
    {
        return $this->isTusIoEndpoint;
    }

    public function setIsTusIoEndpoint(bool $isTusIoEndpoint): self
    {
        $this->isTusIoEndpoint = $isTusIoEndpoint;

        return $this;
    }
}
