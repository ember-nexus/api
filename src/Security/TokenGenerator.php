<?php

namespace App\Security;

use App\Service\ElementManager;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Tuupola\Base58;

class TokenGenerator
{
    private Base58 $encoder;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager
    ) {
        $this->encoder = new Base58();
    }

    public function createNewToken(UuidInterface $userUuid): string
    {
        for ($i = 0; $i < 3; ++$i) {
            $token = $this->createToken();
            $hash = $this->hashToken($token);

            $res = $this->cypherEntityManager->getClient()->runStatement(
                Statement::create(
                    'MATCH (token:Token {hash: $hash}) RETURN token',
                    [
                        'hash' => $hash,
                    ]
                )
            );
            if (0 === $res->count()) {
                break;
            }
        }
        $tokenUuid = Uuid::uuid4();
        $tokenNode = (new NodeElement())
            ->setLabel('Token')
            ->setIdentifier($tokenUuid)
            ->addProperty('hash', $hash);
        $this->elementManager->create($tokenNode);
        $this->elementManager->flush();

        $tokenOwnedByUserRelation = (new RelationElement())
            ->setType('OWNS')
            ->setIdentifier(Uuid::uuid4())
            ->setStartNode($userUuid)
            ->setEndNode($tokenUuid);
        $this->elementManager->create($tokenOwnedByUserRelation);
        $this->elementManager->flush();

        return $token;
    }

    /*
     * Creates a new token in the RFC-8959 scheme
     */
    private function createToken(): string
    {
        return sprintf(
            'secret-token:%s',
            $this->encoder->encode(random_bytes(16))
        );
    }

    public function hashToken(string $token): string
    {
        return hash('sha3-256', $token);
    }
}
