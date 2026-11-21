<?php

declare(strict_types=1);

namespace App\Contract;

use App\Type\Uuid;

interface HasIdInterface
{
    public function getId(): ?Uuid;

    public function setId(?Uuid $id): static;
}
