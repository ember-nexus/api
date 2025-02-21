<?php

declare(strict_types=1);

namespace App\Type;

use App\Contract\NodeElementInterface;
use App\Trait\IdTrait;
use App\Trait\PropertiesTrait;

class NodeElement implements NodeElementInterface
{
    use PropertiesTrait;
    use IdTrait;

    private ?string $label = null;

    public function __construct()
    {
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
