<?php

declare(strict_types=1);

namespace App\Helper;

use App\Factory\Exception\Server500LogicExceptionFactory;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\Node as LaudisNode;
use Laudis\Neo4j\Types\Relationship as LaudisRelationship;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class Neo4jClientHelper
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function getNodeFromLaudisNode(
        LaudisNode $node,
    ): Node {
        $newNode = (new Node())
            ->addLabels($node->getLabels())
            ->addProperties($node->getProperties());
        if ($newNode->hasProperty('id')) {
            $newNode->addIdentifier('id');
        }

        return $newNode;
    }

    public function getRelationFromLaudisRelation(
        LaudisRelationship $relation,
        ?LaudisNode $startNode = null,
        ?LaudisNode $endNode = null,
    ): Relation {
        $newRelation = (new Relation())
            ->setType($relation->getType())
            ->addProperties($relation->getProperties());
        if ($newRelation->hasProperty('id')) {
            $newRelation->addIdentifier('id');
        }
        if (!$startNode || !$endNode) {
            $res = $this->cypherEntityManager->getClient()->runStatement(
                Statement::create(
                    'MATCH (startNode) WHERE elementId(startNode) = $startNodeId',
                    [
                        'startNodeId' => $relation->getStartNodeId(),
                    ]
                )
            );
            $startNode = $res->first()->get('startNode');
            if (!($startNode instanceof LaudisNode)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property startNode as Node, not %s.', get_debug_type($startNode))); // @codeCoverageIgnore
            }
            $res = $this->cypherEntityManager->getClient()->runStatement(
                Statement::create(
                    'MATCH (endNode) WHERE elementId(endNode) = $endNodeId',
                    [
                        'endNodeId' => $relation->getEndNodeId(),
                    ]
                )
            );
            $endNode = $res->first()->get('endNode');
            if (!($endNode instanceof LaudisNode)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property endNode as Node, not %s.', get_debug_type($endNode))); // @codeCoverageIgnore
            }
        }
        $startNode = $this->getNodeFromLaudisNode($startNode);
        $endNode = $this->getNodeFromLaudisNode($endNode);
        /*
         * @var Relation $newRelation
         */
        $newRelation->setStartNode($startNode);
        $newRelation->setEndNode($endNode);

        return $newRelation;
    }
}
