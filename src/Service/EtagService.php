<?php

namespace App\Service;

use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use HashContext;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Predis\Client as RedisClient;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Tuupola\Base58;

use function Safe\pack;
use function Safe\unpack;

class EtagService
{
    private const string HASH_ALGORITHM = 'xxh3';
    private Base58 $encoder;

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private EmberNexusConfiguration $emberNexusConfiguration
    ) {
        $this->encoder = new Base58();
    }

    public function getEtagForElementId(UuidInterface $elementId): string
    {
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                "OPTIONAL MATCH (node {id: \$elementId})\n".
                "OPTIONAL MATCH ()-[relation {id: \$elementId}]->()\n".
                'RETURN node.updated, relation.updated',
                [
                    'elementId' => $elementId->toString(),
                ]
            )
        );
        $updated = $result[0]['node.updated'] ?? $result[0]['relation.updated'] ?? null;
        if (null === $updated) {
            throw new Exception(sprintf('Unable to find node or relation with id %s.', $elementId->toString()));
        }
        if (!($updated instanceof DateTimeZoneId)) {
            throw new Exception(sprintf('Expected variable element.updated to be of type %s, got %s.', DateTimeZoneId::class, get_class($updated)));
        }

        $hashContext = $this->startHashContext();

        hash_update($hashContext, $elementId->getBytes());
        $timestamp = $updated->toDateTime()->getTimestamp();
        $timestampAsBinaryString = pack('C*', ...array_reverse(unpack('C*', pack('L', $timestamp))));
        hash_update($hashContext, $timestampAsBinaryString);

        return $this->getEtagFromHashContext($hashContext);
    }

    private function getEtagFromHashContext(HashContext $hashContext): string
    {
        $rawHash = hash_final($hashContext, true);

        return $this->encoder->encode($rawHash);
    }

    private function startHashContext(): HashContext
    {
        $hashContext = hash_init(self::HASH_ALGORITHM);
        hash_update($hashContext, $this->emberNexusConfiguration->getCacheEtagSeed());

        return $hashContext;
    }
}
