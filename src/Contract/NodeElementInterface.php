<?php

namespace App\Contract;

interface NodeElementInterface extends ElementInterface
{
    public function getLabel(): ?string;

    public function setLabel(?string $label): self;
}
