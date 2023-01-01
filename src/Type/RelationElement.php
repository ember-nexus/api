<?php

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
    private ?UuidInterface $startNode = null;
    private ?UuidInterface $endNode = null;

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

    public function getStartNode(): ?UuidInterface
    {
        return $this->startNode;
    }

    public function setStartNode(?UuidInterface $startNode): self
    {
        $this->startNode = $startNode;

        return $this;
    }

    public function getEndNode(): ?UuidInterface
    {
        return $this->endNode;
    }

    public function setEndNode(?UuidInterface $endNode): self
    {
        $this->endNode = $endNode;

        return $this;
    }
}
