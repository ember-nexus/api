<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Helper\Neo4jClientHelper;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as DocumentEntityManager;
use Laudis\Neo4j\Databags\Statement;

class ElementManager {

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private DocumentEntityManager $documentEntityManager,
        private ElementFragmentizeService $elementFragmentizeService,
        private ElementDefragmentizeService $elementDefragmentizeService
    ){
    }

    public function create(NodeElementInterface|RelationElementInterface $element): void
    {
        $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
        $this->cypherEntityManager->create($fragmentGroup->getCypherFragment());
        $this->documentEntityManager->create($fragmentGroup->getDocumentFragment());
    }

    public function merge(NodeElementInterface|RelationElementInterface $element): void
    {
        $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
        $this->cypherEntityManager->merge($fragmentGroup->getCypherFragment());
        $this->documentEntityManager->merge($fragmentGroup->getDocumentFragment());
    }

    public function delete(NodeElementInterface|RelationElementInterface $element): void
    {
        $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
        $this->cypherEntityManager->delete($fragmentGroup->getCypherFragment());
        $this->documentEntityManager->delete($fragmentGroup->getDocumentFragment());
    }

    public function flush(): void
    {
        $this->cypherEntityManager->flush();
        $this->documentEntityManager->flush();
    }

    public function getNode(UuidInterface $uuid): ?NodeElementInterface
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (node {id: $id}) RETURN node',
                [
                    'id' => $uuid->toString()
                ]
            )
        );
        $cypherFragment = Neo4jClientHelper::getNodeFromLaudisNode($res->first()->get('node'));
        if (!$cypherFragment) {
            return null;
        }
        $documentFragment = $this->documentEntityManager->getClient()->selectDatabase('tion')->selectCollection($cypherFragment->getLabels()[0])->findOne([
            '_id' => $uuid->toString()
        ]);

        print_r($documentFragment);
        exit;


        return null;
    }

    public function get(UuidInterface $uuid): NodeElementInterface|RelationElementInterface
    {
    }

}