<?php

namespace App\Entity;

use Ramsey\Uuid\UuidInterface;

class User
{
    public function __construct(
        private ?UuidInterface $uuid = null,
        private ?string $name = null,
        private ?string $email = null,
    ) {
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
