<?php

namespace App\Controller;

use App\Exception\LogicException;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ElementResponseService;
use App\Service\ElementToRawService;
use App\Service\ProblemJsonGeneratorService;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetUuidController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private CypherEntityManager $cypherEntityManager,
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private ProblemJsonGeneratorService $problemJsonGeneratorService
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'getUuid',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getUuid(string $uuid): Response
    {
        // check if the element is a node or a relation
        $elementUuid = UuidV4::fromString($uuid);
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "OPTIONAL MATCH (node {id: \$elementId})\n".
            "OPTIONAL MATCH (start)-[relation {id: \$elementId}]->(end)\n".
            'RETURN node, relation, start.id, end.id',
            [
                'elementId' => $elementUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            throw new LogicException();
        }
        $node = $res->first()->get('node');
        $relation = $res->first()->get('relation');
        if (null === $node && null === $relation) {
            return $this->problemJsonGeneratorService->createProblemJsonFor404();
        }
        $labels = $res->first()->get('labels');

        $element = $this->elementManager->getElement(UuidV4::fromString($uuid));
        $rawElement = $this->elementToRawService->elementToRaw($element);

        return new JsonResponse($rawElement);
    }
}
