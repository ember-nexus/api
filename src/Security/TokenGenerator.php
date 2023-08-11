<?php

namespace App\Security;

use App\Service\ElementManager;
use App\Type\NodeElement;
use App\Type\RelationElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Safe\DateTime;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Tuupola\Base58;

class TokenGenerator
{
    private Base58 $encoder;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private EmberNexusConfiguration $emberNexusConfiguration
    ) {
        $this->encoder = new Base58();
    }

    public function createNewToken(UuidInterface $userUuid, string $name = null, int $lifetimeInSeconds = null): string
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

        if (null === $lifetimeInSeconds) {
            $lifetimeInSeconds = $this->emberNexusConfiguration->getTokenDefaultLifetimeInSeconds();
        } else {
            if (false !== $this->emberNexusConfiguration->getTokenMaxLifetimeInSeconds()) {
                if ($lifetimeInSeconds > $this->emberNexusConfiguration->getTokenMaxLifetimeInSeconds()) {
                    $lifetimeInSeconds = $this->emberNexusConfiguration->getTokenMaxLifetimeInSeconds();
                }
            }
            if ($lifetimeInSeconds < $this->emberNexusConfiguration->getTokenMinLifetimeInSeconds()) {
                $lifetimeInSeconds = $this->emberNexusConfiguration->getTokenMinLifetimeInSeconds();
            }
        }
        /**
         * @var int $lifetimeInSeconds
         */
        $name ??= (new DateTime())->format('Y-m-d H:i:s');

        $tokenUuid = Uuid::uuid4();
        $tokenNode = (new NodeElement())
            ->setLabel('Token')
            ->setIdentifier($tokenUuid)
            ->addProperties([
                'hash' => $hash,
                'expirationDate' => (new DateTime())->add(new \DateInterval(sprintf('PT%sS', $lifetimeInSeconds))),
                'name' => $name,
            ]);
        $this->elementManager->create($tokenNode);
        $this->elementManager->flush();

        $tokenOwnedByUserRelation = (new RelationElement())
            ->setType('OWNS')
            ->setIdentifier(Uuid::uuid4())
            ->setStart($userUuid)
            ->setEnd($tokenUuid);
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
