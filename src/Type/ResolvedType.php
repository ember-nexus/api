<?php

declare(strict_types=1);

namespace App\Type;

/**
 * The type of an element: the label of a node or the type of a relation.
 */
final readonly class ResolvedType
{
    private const string NODE_PREFIX = 'n:';
    private const string RELATION_PREFIX = 'r:';

    public function __construct(
        private ElementKind $kind,
        private string $type,
    ) {
    }

    /**
     * The form in which types are stored in the lookup table and the Redis overlay: a kind prefix, as a name can be a
     * node label as well as a relationship type (`n:Data`, `r:OWNS`).
     */
    public function encode(): string
    {
        return (ElementKind::NODE === $this->kind ? self::NODE_PREFIX : self::RELATION_PREFIX).$this->type;
    }

    public static function decode(string $encoded): ?self
    {
        $type = substr($encoded, 2);
        if ('' === $type) {
            return null;
        }

        return match (substr($encoded, 0, 2)) {
            self::NODE_PREFIX => new self(ElementKind::NODE, $type),
            self::RELATION_PREFIX => new self(ElementKind::RELATION, $type),
            default => null,
        };
    }

    public function getKind(): ElementKind
    {
        return $this->kind;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isNode(): bool
    {
        return ElementKind::NODE === $this->kind;
    }

    public function isRelation(): bool
    {
        return ElementKind::RELATION === $this->kind;
    }
}
