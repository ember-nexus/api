<?php

declare(strict_types=1);

namespace App\Type;

use App\Contract\RelationElementInterface;
use App\Trait\IdTrait;
use App\Trait\PropertiesTrait;

class RelationElement implements RelationElementInterface
{
    use PropertiesTrait;
    use IdTrait;

    private ?string $type = null;
    private ?Uuid $start = null;
    private ?Uuid $end = null;

    public function __construct()
    {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStart(): ?Uuid
    {
        return $this->start;
    }

    public function setStart(?Uuid $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?Uuid
    {
        return $this->end;
    }

    public function setEnd(?Uuid $end): static
    {
        $this->end = $end;

        return $this;
    }
}
