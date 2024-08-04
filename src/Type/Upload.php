<?php

declare(strict_types=1);

namespace App\Type;

use DateTime;

class Upload extends NodeElement
{
    private UploadVariantType $variant;
    private UploadVariantVersionType $variantVersion;
    private ?UploadConcatType $concat;
    private ?int $uploadLength;
    private bool $isUploadLengthDeferred;
    private int $uploadOffset;
    private DateTime $created;
    private ?DateTime $updated;
    private ?DateTime $expires;

    public function __construct()
    {
        parent::__construct();
    }

    public function getVariant(): UploadVariantType
    {
        return $this->variant;
    }

    public function setVariant(UploadVariantType $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariantVersion(): UploadVariantVersionType
    {
        return $this->variantVersion;
    }

    public function setVariantVersion(UploadVariantVersionType $variantVersion): self
    {
        $this->variantVersion = $variantVersion;

        return $this;
    }

    public function getConcat(): ?UploadConcatType
    {
        return $this->concat;
    }

    public function setConcat(?UploadConcatType $concat): self
    {
        $this->concat = $concat;

        return $this;
    }

    public function getUploadLength(): ?int
    {
        return $this->uploadLength;
    }

    public function setUploadLength(?int $uploadLength): self
    {
        $this->uploadLength = $uploadLength;

        return $this;
    }

    public function isUploadLengthDeferred(): bool
    {
        return $this->isUploadLengthDeferred;
    }

    public function setIsUploadLengthDeferred(bool $isUploadLengthDeferred): self
    {
        $this->isUploadLengthDeferred = $isUploadLengthDeferred;

        return $this;
    }

    public function getUploadOffset(): int
    {
        return $this->uploadOffset;
    }

    public function setUploadOffset(int $uploadOffset): self
    {
        $this->uploadOffset = $uploadOffset;

        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    public function setUpdated(?DateTime $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getExpires(): ?DateTime
    {
        return $this->expires;
    }

    public function setExpires(?DateTime $expires): self
    {
        $this->expires = $expires;

        return $this;
    }
}
