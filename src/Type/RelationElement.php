<?php

declare(strict_types=1);

namespace App\Type;

use App\Contract\RelationElementInterface;
use App\Trait\IdentifierTrait;
use App\Trait\PropertiesTrait;
use Ramsey\Uuid\UuidInterface;

class RelationElement implements RelationElementInterface
{
    use PropertiesTrait;
    use IdentifierTrait;

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

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStart(): ?UuidInterface
    {
        return $this->start;
    }

    public function setStart(?UuidInterface $uuid): self
    {
        $this->start = $uuid;

        return $this;
    }

    public function getEnd(): ?UuidInterface
    {
        return $this->end;
    }

    public function setEnd(?UuidInterface $uuid): self
    {
        $this->end = $uuid;

        return $this;
    }
}
