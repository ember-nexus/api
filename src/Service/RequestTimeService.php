<?php

namespace App\Service;

use DateTimeInterface;

class RequestTimeService
{
    private DateTimeInterface $requestStart;

    public function getRequestStart(): DateTimeInterface
    {
        return $this->requestStart;
    }

    public function setRequestStart(DateTimeInterface $requestStart): self
    {
        $this->requestStart = $requestStart;

        return $this;
    }
}
