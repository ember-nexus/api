<?php

declare(strict_types=1);

namespace App\Type;

use App\Contract\RelationElementInterface;
use App\Trait\IdTrait;
use App\Trait\PropertiesTrait;
use Ramsey\Uuid\UuidInterface;

class RelationElement implements RelationElementInterface
{
    use PropertiesTrait;
    use IdTrait;

    private ?string $type = null;
    private ?UuidInterface $start = null;
    private ?UuidInterface $end = null;

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

    public function getStart(): ?UuidInterface
    {
        return $this->start;
    }

    public function setStart(?UuidInterface $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?UuidInterface
    {
        return $this->end;
    }

    public function setEnd(?UuidInterface $end): static
    {
        $this->end = $end;

        return $this;
    }
}
