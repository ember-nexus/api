<?php

declare(strict_types=1);

namespace App\Contract;

use Ramsey\Uuid\UuidInterface;

interface HasIdentifierInterface
{
    public function getIdentifier(): ?UuidInterface;

    public function setIdentifier(?UuidInterface $identifier): self;
}
