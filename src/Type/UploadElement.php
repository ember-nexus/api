<?php

declare(strict_types=1);

namespace App\Type;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Service\FileUtilService;
use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class UploadElement extends NodeElement
{
    private ?int $uploadLength = null;
    private int $uploadOffset = 0;
    private bool $uploadComplete = false;
    private ?UuidInterface $uploadTarget = null;
    private int $alreadyUploadedChunks = 0;
    private ?UuidInterface $uploadOwner = null;
    private string $extension = FileUtilService::DEFAULT_EXTENSION;
    private ?DateTime $created = null;
    private ?DateTime $updated = null;
    private ?DateTime $expires = null;

    public function __construct()
    {
        parent::__construct();
    }

    public static function createFromElement(NodeElementInterface|RelationElementInterface $element): self
    {
        $upload = new self();

        if (!($element instanceof NodeElementInterface)) {
            throw new Exception('Upload element must be a node, not a relation.');
        }

        $label = $element->getLabel() ?? 'no-label';
        if ('Upload' !== $label) {
            throw new Exception(sprintf('Can not cast element of type %s to upload.', $label));
        }

        $properties = $element->getProperties();

        if (array_key_exists('uploadLength', $properties)) {
            $uploadLength = $properties['uploadLength'];
            if (is_int($uploadLength)) {
                $upload->setUploadLength($uploadLength);
            }
        }

        if (array_key_exists('uploadOffset', $properties)) {
            $uploadOffset = $properties['uploadOffset'];
            if (is_int($uploadOffset)) {
                $upload->setUploadOffset($uploadOffset);
            }
        }

        if (array_key_exists('uploadComplete', $properties)) {
            $uploadComplete = $properties['uploadComplete'];
            if (is_bool($uploadComplete)) {
                $upload->setUploadComplete($uploadComplete);
            }
        }

        if (array_key_exists('uploadTarget', $properties)) {
            $uploadTarget = $properties['uploadTarget'];
            if (is_string($uploadTarget)) {
                $uploadTarget = Uuid::fromString($uploadTarget);
                $upload->setUploadTarget($uploadTarget);
            }
        }

        if (array_key_exists('alreadyUploadedChunks', $properties)) {
            $alreadyUploadedChunks = $properties['alreadyUploadedChunks'];
            if (is_int($alreadyUploadedChunks)) {
                $upload->setAlreadyUploadedChunks($alreadyUploadedChunks);
            }
        }

        if (array_key_exists('uploadOwner', $properties)) {
            $uploadOwner = $properties['uploadOwner'];
            if (is_string($uploadOwner)) {
                $uploadOwner = Uuid::fromString($uploadOwner);
                $upload->setUploadOwner($uploadOwner);
            }
        }

        if (array_key_exists('extension', $properties)) {
            $extension = $properties['extension'];
            if (is_string($extension)) {
                $upload->setExtension($extension);
            }
        }

        if (array_key_exists('created', $properties)) {
            $created = $properties['created'];
            if ($created instanceof DateTime) {
                $upload->setCreated($created);
            }
        }

        if (array_key_exists('updated', $properties)) {
            $updated = $properties['updated'];
            if ($updated instanceof DateTime) {
                $upload->setUpdated($updated);
            }
        }

        if (array_key_exists('expires', $properties)) {
            $expires = $properties['expires'];
            if ($expires instanceof DateTime) {
                $upload->setExpires($expires);
            }
        }

        return $upload;
    }

    public function getUploadLength(): ?int
    {
        return $this->uploadLength;
    }

    public function setUploadLength(?int $uploadLength): static
    {
        $this->addProperty('uploadLength', $uploadLength);
        $this->uploadLength = $uploadLength;

        return $this;
    }

    public function getUploadOffset(): int
    {
        return $this->uploadOffset;
    }

    public function setUploadOffset(int $uploadOffset): static
    {
        $this->addProperty('uploadOffset', $uploadOffset);
        $this->uploadOffset = $uploadOffset;

        return $this;
    }

    public function isUploadComplete(): bool
    {
        return $this->uploadComplete;
    }

    public function setUploadComplete(bool $uploadComplete): static
    {
        $this->addProperty('uploadComplete', $uploadComplete);
        $this->uploadComplete = $uploadComplete;

        return $this;
    }

    public function getUploadTarget(): ?UuidInterface
    {
        return $this->uploadTarget;
    }

    public function setUploadTarget(?UuidInterface $uploadTarget): static
    {
        $this->addProperty('uploadTarget', $uploadTarget);
        $this->uploadTarget = $uploadTarget;

        return $this;
    }

    public function getAlreadyUploadedChunks(): int
    {
        return $this->alreadyUploadedChunks;
    }

    public function setAlreadyUploadedChunks(int $alreadyUploadedChunks): static
    {
        $this->addProperty('alreadyUploadedChunks', $alreadyUploadedChunks);
        $this->alreadyUploadedChunks = $alreadyUploadedChunks;

        return $this;
    }

    public function getUploadOwner(): ?UuidInterface
    {
        return $this->uploadOwner;
    }

    public function setUploadOwner(?UuidInterface $uploadOwner): static
    {
        $this->addProperty('uploadOwner', $uploadOwner);
        $this->uploadOwner = $uploadOwner;

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->addProperty('extension', $extension);
        $this->extension = $extension;
        return $this;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(?DateTime $created): static
    {
        $this->addProperty('created', $created);
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    public function setUpdated(?DateTime $updated): static
    {
        $this->addProperty('updated', $updated);
        $this->updated = $updated;

        return $this;
    }

    public function getExpires(): ?DateTime
    {
        return $this->expires;
    }

    public function setExpires(?DateTime $expires): static
    {
        $this->addProperty('expires', $expires);
        $this->expires = $expires;

        return $this;
    }
}
