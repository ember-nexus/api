<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * Deletes a node together with every relation touching it, each going through ElementManager's normal delete
 * lifecycle. A node's `DETACH DELETE` is a single Cypher statement that never dispatches delete events for the
 * relations it cascades away, so event listeners would otherwise never fire for them; this cascading behavior is
 * opt-in for call sites that want it, not built into ElementManager itself.
 */
class DeletionService
{
    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * Flushes once, after every relation touching the element (if it's a node) has been queued for delete.
     */
    public function delete(NodeElementInterface|RelationElementInterface $element): void
    {
        if ($element instanceof NodeElementInterface) {
            $nodeId = $element->getId();
            if (null !== $nodeId) {
                foreach ($this->getRelationsTouching($nodeId) as $relation) {
                    $this->elementManager->delete($relation);
                }
            }
        }

        $this->elementManager->delete($element);
        $this->elementManager->flush();
    }

    /**
     * @return RelationElementInterface[]
     */
    private function getRelationsTouching(UuidInterface $nodeId): array
    {
        // DISTINCT matters for a self-referencing relation (both ends on the same node): an undirected pattern like
        // this one would otherwise match it twice (once per traversal direction), which would queue it for delete
        // twice below and dispatch its delete events twice - each of which is wrong on its own.
        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (node {id: \$nodeId})-[relation]-()\n".
            'RETURN DISTINCT relation.id AS relationId',
            [
                'nodeId' => $nodeId->toString(),
            ]
        ));

        $relations = [];
        $seenRelationIds = [];
        foreach ($result as $row) {
            $rawRelationId = $row['relationId'];
            if (!is_string($rawRelationId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property relationId as string, not %s.', get_debug_type($rawRelationId))); // @codeCoverageIgnore
            }
            // defense in depth on top of the query's DISTINCT: queuing (and therefore dispatching delete events
            // for) the same relation twice is wrong regardless of why a duplicate row showed up.
            if (isset($seenRelationIds[$rawRelationId])) {
                continue;
            }
            $seenRelationIds[$rawRelationId] = true;

            // can race a concurrent delete of the same relation between this query and the hydration below; simply
            // skipping it is correct, there is nothing left to cascade-delete it along with the node for
            $relation = $this->elementManager->getRelation(Uuid::fromString($rawRelationId));
            if (null !== $relation) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }
}
