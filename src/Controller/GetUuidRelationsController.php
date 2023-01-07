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

class GetUuidRelationsController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private Neo4jClientHelper $neo4jClientHelper,
        private ElementToRawService $elementToRawService,
        private ElementManager $elementManager,
    ) {
    }

    #[Route(
        '/{uuid}/relations',
        name: 'getUuidRelations',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getUuidRelations(string $uuid): Response
    {
        $relations = [];
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            'MATCH ({id: $nodeId})-[relation]-() RETURN relation{.id}',
            [
                'nodeId' => $uuid,
            ]
        ));
        foreach ($res as $resultSet) {
            $relations[] = $resultSet->get('relation')->get('id');
        }
        $finalRelations = [];
        foreach ($relations as $relation) {
            /*
             * @var RelationInterface $relation
             */
            $finalRelations[] = $this->elementManager->getRelation(UuidV4::fromString($relation));
        }

        $rawRelations = [];
        foreach ($finalRelations as $relation) {
            $rawRelations[] = $this->elementToRawService->elementToRaw($relation);
        }

        return new JsonResponse($rawRelations);
    }
}
