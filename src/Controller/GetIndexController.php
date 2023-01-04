<?php

namespace App\Controller;

use App\Helper\Neo4jClientHelper;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetIndexController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private Neo4jClientHelper $neo4jClientHelper,
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService
    ) {
    }

    #[Route(
        '/',
        name: 'getIndex',
        methods: ['GET']
    )]
    public function getIndex(): Response
    {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[:PART_OF_GROUP*0..]->()-[:OWNS]->(element)\n".
            'RETURN element',
            [
                'userId' => '6ce3006b-6b7f-4770-8075-d2bf91804d14',
            ]
        ));
        $nodes = [];
        foreach ($res as $resultSet) {
            $nodes[] = $this->neo4jClientHelper->getNodeFromLaudisNode($resultSet->get('element'));
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
    }
}
