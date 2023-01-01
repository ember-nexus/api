<?php

declare(strict_types=1);

namespace App\Contract;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends StoppableEventInterface
{
    public function stopPropagation(): void;
}
