<?php

declare(strict_types=1);

namespace App\Security;

use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Service\ElementManager;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\TokenStateType;
use DateInterval;
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
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
        $this->encoder = new Base58();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createNewToken(UuidInterface $userId, array $data = [], ?int $lifetimeInSeconds = null): string
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
            /**
             * @phpstan-ignore-next-line greater.alwaysTrue
             */
            if ($res->count() > 0 && 2 == $i) {
                throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to generate new token hash without collision. Please repeat your last call at a later time.');
            }
        }

        if (null === $lifetimeInSeconds) {
            $lifetimeInSeconds = $this->emberNexusConfiguration->getTokenDefaultLifetimeInSeconds();
        } else {
            $tokenMaxLifetimeInSeconds = $this->emberNexusConfiguration->getTokenMaxLifetimeInSeconds();
            if (false !== $tokenMaxLifetimeInSeconds) {
                if ($lifetimeInSeconds > $tokenMaxLifetimeInSeconds) {
                    $lifetimeInSeconds = $tokenMaxLifetimeInSeconds;
                }
            }
            if ($lifetimeInSeconds < $this->emberNexusConfiguration->getTokenMinLifetimeInSeconds()) {
                $lifetimeInSeconds = $this->emberNexusConfiguration->getTokenMinLifetimeInSeconds();
            }
        }

        $tokenId = Uuid::uuid4();
        $tokenNode = (new NodeElement())
            ->setLabel('Token')
            ->setId($tokenId)
            ->addProperty('name', (new DateTime())->format('Y-m-d H:i:s'))
            ->addProperties($data)
            ->addProperties([
                'hash' => $hash,
                'expirationDate' => (new DateTime())->add(new DateInterval(sprintf('PT%sS', $lifetimeInSeconds))),
                'state' => TokenStateType::ACTIVE->value,
            ]);
        $this->elementManager->create($tokenNode);
        $this->elementManager->flush();

        $tokenOwnedByUserRelation = (new RelationElement())
            ->setType('OWNS')
            ->setId(Uuid::uuid4())
            ->setStart($userId)
            ->setEnd($tokenId);
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
