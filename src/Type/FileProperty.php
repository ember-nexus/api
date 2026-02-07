<?php

declare(strict_types=1);

namespace App\Type;


use App\Service\FileService;
use JsonSerializable;

class FileProperty implements JsonSerializable
{

    private string $extension = FileService::DEFAULT_EXTENSION;

    public function __construct()
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'extension' => $this->getExtension()
        ];
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;
        return $this;
    }

}
