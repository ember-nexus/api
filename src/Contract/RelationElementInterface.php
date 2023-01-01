<?php

namespace App\Contract;

use Ramsey\Uuid\UuidInterface;

interface RelationElementInterface extends ElementInterface
{
    public function getType(): ?string;

    public function setType(?string $type): self;

    public function getStartNode(): ?UuidInterface;

    public function setStartNode(?UuidInterface $uuid): self;

    public function getEndNode(): ?UuidInterface;

    public function setEndNode(?UuidInterface $uuid): self;
}
