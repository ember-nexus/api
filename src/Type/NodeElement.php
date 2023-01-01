<?php

namespace App\Type;

use App\Contract\NodeElementInterface;
use App\Trait\IdentifierTrait;
use App\Trait\PropertiesTrait;

class NodeElement implements NodeElementInterface
{
    use PropertiesTrait;
    use IdentifierTrait;

    private ?string $label = null;

    public function __construct()
    {
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }
}
