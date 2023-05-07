<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use App\Type\AccessType;
use App\Type\ElementType;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetElementController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private CypherEntityManager $cypherEntityManager
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'getElement',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getElement(string $uuid): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        $access = false;
        if (ElementType::NODE === $this->checkElementType($elementUuid)) {
            $access = $this->hasAccessToNode($userUuid, $elementUuid, AccessType::READ);
        } else {
            // check relation access etc.
        }
        if (!$access) {
            throw new ClientNotFoundException();
        }

        return $this->elementResponseService->buildElementResponseFromUuid($elementUuid);
    }

    public function hasAccessToNode(UuidInterface $userUuid, UuidInterface $elementUuid, AccessType $accessType): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
                "MATCH (element {id: \$elementId})\n".
                'MATCH (user)-[:IS_IN_GROUP*0..]->()-[relations:OWNS|HAS_'.$accessType->value."_ACCESS*1..]->(element)\n".
                "WHERE\n".
                "  ALL(relation in relations WHERE\n".
                "    type(relation) = \"OWNS\"\n".
                "    OR\n".
                "    (\n".
                '      type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
                "      AND\n".
                "      (\n".
                "        relation.onLabel IS NULL\n".
                "        OR\n".
                "        relation.onLabel IN labels(element)\n".
                "      )\n".
                "      AND\n".
                "      (\n".
                "        relation.onParentLabel IS NULL\n".
                "        OR\n".
                "        relation.onParentLabel IN labels(element)\n".
                "      )\n".
                "      AND\n".
                "      (\n".
                "        relation.onState IS NULL\n".
                "        OR\n".
                "        (element)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
                "      )\n".
                "      AND\n".
                "      (\n".
                "        relation.onCreatedByUser IS NULL\n".
                "        OR\n".
                "        (element)<-[:CREATED_BY*]-(user)\n".
                "      )\n".
                "    )\n".
                "  )\n".
                'RETURN user, element;',
            [
                'userId' => $userUuid->toString(),
                'elementId' => $elementUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return false;
        }

        return true;
    }

    public function checkElementType(UuidInterface $elementUuid): ?ElementType
    {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "OPTIONAL MATCH (node {id: \$elementId})\n".
            "OPTIONAL MATCH ()-[relation {id: \$elementId}]-()\n".
            'RETURN node.id, relation.id',
            [
                'elementId' => $elementUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return null;
        }
        if (null !== $res->first()->get('node.id')) {
            return ElementType::NODE;
        }
        if (null !== $res->first()->get('relation.id')) {
            return ElementType::RELATION;
        }

        return null;
    }
}
