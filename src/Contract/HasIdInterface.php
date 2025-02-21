<?php

declare(strict_types=1);

namespace App\Contract;

use Ramsey\Uuid\UuidInterface;

interface HasIdInterface
{
    public function getId(): ?UuidInterface;

    public function setId(?UuidInterface $id): static;
}
