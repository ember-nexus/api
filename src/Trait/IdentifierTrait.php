<?php

declare(strict_types=1);

namespace App\Trait;

use Ramsey\Uuid\UuidInterface;

trait IdentifierTrait
{
    private ?UuidInterface $identifier = null;

    public function getIdentifier(): ?UuidInterface
    {
        return $this->identifier;
    }

    public function setIdentifier(?UuidInterface $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }
}
