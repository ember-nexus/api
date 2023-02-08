<?php

namespace App\Contract;

use Ramsey\Uuid\UuidInterface;

interface RelationElementInterface extends ElementInterface
{
    public function getType(): ?string;

    public function setType(?string $type): self;

    public function getStart(): ?UuidInterface;

    public function setStart(?UuidInterface $uuid): self;

    public function getEnd(): ?UuidInterface;

    public function setEnd(?UuidInterface $uuid): self;
}
