<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;
use DateTimeInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

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
