<?php

declare(strict_types=1);

namespace App\Type;

use DateTime;

class Upload extends NodeElement
{
    private ?int $uploadLength = null;
    private int $uploadOffset = 0;
    private bool $uploadComplete = false;
    private ?DateTime $created = null;
    private ?DateTime $updated = null;
    private ?DateTime $expires = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function getUploadLength(): ?int
    {
        return $this->uploadLength;
    }

    public function setUploadLength(?int $uploadLength): static
    {
        $this->uploadLength = $uploadLength;

        return $this;
    }

    public function getUploadOffset(): int
    {
        return $this->uploadOffset;
    }

    public function setUploadOffset(int $uploadOffset): static
    {
        $this->uploadOffset = $uploadOffset;

        return $this;
    }

    public function isUploadComplete(): bool
    {
        return $this->uploadComplete;
    }

    public function setUploadComplete(bool $uploadComplete): static
    {
        $this->uploadComplete = $uploadComplete;

        return $this;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(?DateTime $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    public function setUpdated(?DateTime $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getExpires(): ?DateTime
    {
        return $this->expires;
    }

    public function setExpires(?DateTime $expires): static
    {
        $this->expires = $expires;

        return $this;
    }
}
