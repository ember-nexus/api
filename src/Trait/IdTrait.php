<?php

declare(strict_types=1);

namespace App\Trait;

use App\Type\Uuid;

trait IdTrait
{
    private ?Uuid $id = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(?Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }
}
