<?php

declare(strict_types=1);

namespace App\Trait;

use Ramsey\Uuid\UuidInterface;

trait IdTrait
{
    private ?UuidInterface $id = null;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $id): static
    {
        $this->id = $id;

        return $this;
    }
}
