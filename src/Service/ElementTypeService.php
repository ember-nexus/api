<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\MphfInterface;
use App\Helper\CypherHelper;
use App\Service\Mphf\ElementIdFilterService;
use App\Service\Mphf\ElementTypeOverlayService;
use App\Service\Mphf\MphfVersionSynchronizer;
use App\Type\ElementKind;
use App\Type\ResolvedType;
use App\Type\Uuid;
use Laudis\Neo4j\Databags\Statement;
use OutOfBoundsException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Throwable;

/**
 * Resolves the type (label of a node, type of a relation) of an element id, so that all element queries can use typed,
 * index backed patterns instead of scanning all nodes or relations.
 *
 * Order: per request memo, Redis overlay (active key, then the previous one), the `.mphf` lookup table, and only if no
 * table is loaded the untyped query. The table has no membership information, so its answer is a candidate: with a
 * table loaded and the overlay covering everything created since, a typed query without result means that the element
 * does not exist ("strict mode"). Strict mode only holds while the loaded table is the one Redis names as current; a
 * container which still has an older table, or a non strict configuration, verifies the candidate with one indexed
 * query and falls back to the untyped query on a miss.
 */
class ElementTypeService implements ResetInterface
{
    /**
     * @var array<string, ResolvedType|null>
     */
    private array $memo = [];

    public function __construct(
        private ElementTypeOverlayService $elementTypeOverlayService,
        private MphfInterface $mphf,
        private MphfVersionSynchronizer $mphfVersionSynchronizer,
        private ElementIdFilterService $elementIdFilterService,
        private CypherEntityManager $cypherEntityManager,
        private LoggerInterface $logger,
        private bool $mphfStrict,
    ) {
    }

    /**
     * @return ResolvedType|null null if the element does not exist (or, with a stale table, if it can't be found)
     */
    public function resolve(Uuid $id): ?ResolvedType
    {
        $key = $id->toString();
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        return $this->memo[$key] = $this->doResolve($key);
    }

    /**
     * Resolves many ids with one Redis round trip for the overlay instead of one per id.
     *
     * @param Uuid[] $ids
     *
     * @return array<string, ResolvedType|null> by id string; ids which could not be resolved map to null
     */
    public function resolveMany(array $ids): array
    {
        $resolved = [];
        $missing = [];
        foreach ($ids as $id) {
            $key = $id->toString();
            if (array_key_exists($key, $this->memo)) {
                $resolved[$key] = $this->memo[$key];
            } else {
                $missing[$key] = $key;
            }
        }
        if ([] === $missing) {
            return $resolved;
        }
        $this->mphfVersionSynchronizer->ensureSynchronized();
        $overlay = $this->lookupOverlayMany(array_values($missing));
        foreach ($missing as $key) {
            $resolved[$key] = $this->memo[$key] = $this->doResolve($key, true, $overlay[$key] ?? null);
        }

        return $resolved;
    }

    /**
     * Whether an element with this id exists. If the id filter is complete, an unknown id is answered without a query.
     * Otherwise the type from the lookup is only a candidate (wrong for an id which does
     * not exist), so one typed, index backed query with it decides; nothing else of the element is loaded.
     */
    public function elementExists(Uuid $id): bool
    {
        // the Bloom filter has no false negatives: an id which it doesn't know can't exist, no query needed
        if (false === $this->elementIdFilterService->mightExist($id->toString())) {
            return false;
        }
        $resolved = $this->resolve($id);

        return null !== $resolved && $this->exists($id->toString(), $resolved);
    }

    /**
     * The label of the node, null if there is no node with this id.
     */
    public function resolveNodeLabel(Uuid $id): ?string
    {
        $resolved = $this->resolve($id);

        return null !== $resolved && $resolved->isNode() ? $resolved->getType() : null;
    }

    /**
     * The type of the relation, null if there is no relation with this id.
     */
    public function resolveRelationType(Uuid $id): ?string
    {
        $resolved = $this->resolve($id);

        return null !== $resolved && $resolved->isRelation() ? $resolved->getType() : null;
    }

    /**
     * Forgets everything resolved so far; has to be called after elements were deleted or created with the same id in
     * the same process.
     */
    public function forget(Uuid $id): void
    {
        unset($this->memo[$id->toString()]);
    }

    public function reset(): void
    {
        $this->memo = [];
    }

    private function doResolve(string $id, bool $overlayKnown = false, ?string $overlayType = null): ?ResolvedType
    {
        $this->mphfVersionSynchronizer->ensureSynchronized();

        $type = $overlayKnown ? $overlayType : $this->lookupOverlay($id);
        $fromOverlay = null !== $type;
        if (null === $type) {
            $candidate = $this->mphf->lookup($id);
            if ('' !== $candidate) {
                $type = $candidate;
            } elseif ('' !== $this->mphf->version()) {
                // the table is loaded and has no candidate; as strict mode relies on the table plus the overlay
                // covering everything, the element doesn't exist
                return $this->shouldVerify() ? $this->resolveUntyped($id) : null;
            } else {
                return $this->resolveUntyped($id);
            }
        }

        $resolved = ResolvedType::decode($type);
        if (null === $resolved) {
            // not in the format written by this application, e.g. a table of an older version
            return $fromOverlay || $this->shouldVerify() ? $this->resolveUntyped($id) : null;
        }
        if (!$fromOverlay && $this->shouldVerify() && !$this->exists($id, $resolved)) {
            return $this->resolveUntyped($id);
        }

        return $resolved;
    }

    private function lookupOverlay(string $id): ?string
    {
        try {
            return $this->elementTypeOverlayService->lookup($id);
        } catch (Throwable $throwable) {
            $this->logger->error('Unable to read the element type overlay from Redis; a type resolved without it may produce a false 404.', ['exception' => $throwable]);

            return null;
        }
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, string>
     */
    private function lookupOverlayMany(array $ids): array
    {
        try {
            return $this->elementTypeOverlayService->lookupMany($ids);
        } catch (Throwable $throwable) {
            $this->logger->error('Unable to read the element type overlay from Redis; a type resolved without it may produce a false 404.', ['exception' => $throwable]);

            return [];
        }
    }

    private function shouldVerify(): bool
    {
        return !$this->mphfStrict || !$this->mphfVersionSynchronizer->isLoadedVersionCurrent();
    }

    private function exists(string $id, ResolvedType $resolved): bool
    {
        $escaped = CypherHelper::escapeIdentifier($resolved->getType());
        $query = $resolved->isNode()
            ? sprintf('MATCH (e:%s {id: $id}) RETURN 1 AS found LIMIT 1', $escaped)
            : sprintf('MATCH ()-[e:%s {id: $id}]->() RETURN 1 AS found LIMIT 1', $escaped);

        return $this->cypherEntityManager->getClient()->runStatement(Statement::create($query, ['id' => $id]))->count() > 0;
    }

    /**
     * Today's behaviour: finds the type with one query over all nodes and relations. Slow on big datasets, therefore
     * only used while no table is loaded (or to confirm a candidate in non strict mode).
     */
    private function resolveUntyped(string $id): ?ResolvedType
    {
        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (n {id: $id}) RETURN \'node\' AS kind, labels(n)[0] AS type LIMIT 1 '.
            'UNION '.
            'MATCH ()-[r {id: $id}]->() RETURN \'relation\' AS kind, type(r) AS type LIMIT 1',
            ['id' => $id]
        ));
        try {
            $row = $result->first();
        } catch (OutOfBoundsException) {
            return null;
        }
        $type = $row->get('type');
        if (!is_string($type)) {
            return null;
        }

        return new ResolvedType('node' === $row->get('kind') ? ElementKind::NODE : ElementKind::RELATION, $type);
    }
}
