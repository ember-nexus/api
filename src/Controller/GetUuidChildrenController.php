<?php

namespace App\Controller;

use App\Helper\Neo4jClientHelper;
use App\Helper\Regex;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetUuidChildrenController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private Neo4jClientHelper $neo4jClientHelper,
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService
    ) {
    }

    #[Route(
        '/{uuid}/children',
        name: 'getUuidChildren',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getUuidChildren(string $uuid): Response
    {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (parent {id: \$parentId})\n".
            "MATCH (parent)-[:OWNS]->(children)\n".
            'RETURN children',
            [
                'parentId' => $uuid,
            ]
        ));
        $nodes = [];
        foreach ($res as $resultSet) {
            $nodes[] = $this->neo4jClientHelper->getNodeFromLaudisNode($resultSet->get('children'));
        }

        $finalNodes = [];
        foreach ($nodes as $node) {
            /*
             * @var NodeInterface $node
             */
            $finalNodes[] = $this->elementManager->getNode(UuidV4::fromString($node->getIdentifier('id')));
        }

        $rawNodes = [];
        foreach ($finalNodes as $node) {
            $rawNodes[] = $this->elementToRawService->elementToRaw($node);
        }

        return new JsonResponse($rawNodes);

        // return new Response('it worked :D');
    }
}
