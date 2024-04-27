<?php

declare(strict_types=1);

namespace App\Contract;

use Ramsey\Uuid\UuidInterface;

interface RelationElementInterface extends ElementInterface
{
    public function getType(): ?string;

    public function setType(?string $type): self;

    public function getStart(): ?UuidInterface;

    public function setStart(?UuidInterface $start): self;

    public function getEnd(): ?UuidInterface;

    public function setEnd(?UuidInterface $end): self;
}
