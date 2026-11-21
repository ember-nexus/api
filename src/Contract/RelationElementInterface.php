<?php

declare(strict_types=1);

namespace App\Contract;

use App\Type\Uuid;

interface RelationElementInterface extends ElementInterface
{
    public function getType(): ?string;

    public function setType(?string $type): static;

    public function getStart(): ?Uuid;

    public function setStart(?Uuid $start): static;

    public function getEnd(): ?Uuid;

    public function setEnd(?Uuid $end): static;
}
