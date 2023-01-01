<?php

namespace App\Repository;

use App\Entity\User;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\Node as LaudisNode;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class UserRepository
{
    public function __construct(private CypherEntityManager $cypherEntityManager)
    {
    }

    public function getUserByUuid(UuidInterface $uuid): ?User
    {
        /**
         * @var $res SummarizedResult
         */
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (user:User {id: $id}) RETURN user',
            [
                'id' => $uuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return null;
        }
        if ($res->count() > 1) {
            throw new \Exception('Found more than one user with the uuid xxx');
        }
        /**
         * @var $userNode LaudisNode
         */
        $userNode = $res->first()->get('user');

        return $this->createUserFromNode($userNode);
    }

    private function createUserFromNode(LaudisNode $userNode): User
    {
        $user = (new User())
            ->setUuid(UuidV4::fromString($userNode->getProperty('id')))
            ->setName($userNode->getProperty('name'))
            ->setEmail($userNode->getProperty('email'));

        return $user;
    }

    public function persistUser(User $user): self
    {
        $userNode = (new Node())
            ->addProperty('id', $user->getUuid()->toString())
            ->addProperty('name', $user->getName())
            ->addProperty('email', $user->getEmail())
            ->addIdentifier('id');
        $this->cypherEntityManager->merge($userNode);
        $this->cypherEntityManager->flush();

        return $this;
    }
}
