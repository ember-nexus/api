<?php

declare(strict_types=1);

namespace App\Service\Index;

use function Safe\preg_replace;

/**
 * Names of the Neo4j id indices which are managed by Ember Nexus: `ember_nexus_node_id_<slug>_<h8>` and
 * `ember_nexus_relation_id_<slug>_<h8>`. `slug` is the lowercased type with every run of characters outside `[a-z0-9]`
 * replaced by `_` (at most 40 characters), `h8` the first 8 hex characters of the SHA-256 of the type, so names stay
 * unique (types differing only in case or special characters) and deterministic.
 */
class IndexNameService
{
    public const string PREFIX = 'ember_nexus_';
    private const int MAX_SLUG_LENGTH = 40;

    public function getNodeIndexName(string $label): string
    {
        return $this->getName('node', $label);
    }

    public function getRelationIndexName(string $type): string
    {
        return $this->getName('relation', $type);
    }

    public function isManagedIndexName(string $name): bool
    {
        return str_starts_with($name, self::PREFIX);
    }

    private function getName(string $kind, string $type): string
    {
        /** @var string $slug */
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($type));
        $slug = substr($slug, 0, self::MAX_SLUG_LENGTH);

        return sprintf('%s%s_id_%s_%s', self::PREFIX, $kind, $slug, substr(hash('sha256', $type), 0, 8));
    }
}
