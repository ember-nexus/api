<?php

declare(strict_types=1);

namespace App\Type;

use Ramsey\Uuid\UuidInterface;

class ElementHydrationPartialElement
{
    public function __construct(
        private UuidInterface $id,
        private array $metadata = [],
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }
}
