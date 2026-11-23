<?php

declare(strict_types=1);

namespace App\Antlr;

use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\RedisKeyFactory;
use Cypher25Lexer;
use CypherPathSubset;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

class CypherPathSubsetGrammar
{
    public const string PROFILER_NAME = 'CypherPathSubsetGrammar::validateQuery';
    public const int REDIS_QUERY_TTL_IN_SECONDS = 3600;

    public function __construct(
        private AntlrSyntaxErrorListenerFactory $antlrSyntaxErrorListenerFactory,
        private Stopwatch $stopwatch,
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private LoggerInterface $logger,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    /**
     * Replaces `RETURN path` by the ids of its nodes and relations. The search only works with the ids of the elements
     * of a path (access check, hydration), so Neo4j doesn't have to send, and PHP doesn't have to decode, all nodes and
     * relations with all of their properties.
     */
    public const string IDS_RETURN_ITEMS = '[node IN nodes(path) | node.id] AS nodeIds, [relation IN relationships(path) | relation.id] AS relationIds';

    /**
     * Validates the query and returns the query to execute, cached in Redis like the validation itself. Queries which
     * can't be rewritten safely are returned unchanged.
     */
    public function prepareQuery(string $cypherQuery): string
    {
        $redisKey = (string) $this->redisKeyTypeFactory->getRewrittenQueryCypherPathSubsetRedisKey($cypherQuery);
        $cached = $this->redisClient->get($redisKey);
        if (is_string($cached)) {
            return $cached;
        }

        $this->stopwatch->start(self::PROFILER_NAME);
        $singleReturnPathListener = $this->analyseQuery($cypherQuery);
        $range = $singleReturnPathListener->getIdsRewriteRange();
        $prepared = $cypherQuery;
        if (null !== $range) {
            $prepared = mb_substr($cypherQuery, 0, $range['start']).self::IDS_RETURN_ITEMS.mb_substr($cypherQuery, $range['stop'] + 1);
        }
        $this->redisClient->set($redisKey, $prepared, 'EX', self::REDIS_QUERY_TTL_IN_SECONDS);
        $this->redisClient->set((string) $this->redisKeyTypeFactory->getValidatedQueryCypherPathSubsetRedisKey($cypherQuery), true, 'EX', self::REDIS_QUERY_TTL_IN_SECONDS);
        $this->stopwatch->stop(self::PROFILER_NAME);

        return $prepared;
    }

    private function analyseQuery(string $cypherQuery): SingleReturnPathListener
    {
        $inputStream = InputStream::fromString($cypherQuery);

        $lexer = new Cypher25Lexer($inputStream);
        $tokenStream = new CommonTokenStream($lexer);
        $parser = new CypherPathSubset($tokenStream);

        $parser->removeErrorListeners();
        $parser->addErrorListener($this->antlrSyntaxErrorListenerFactory->createNewAntlrSyntaxErrorListener());

        $tree = $parser->statements();
        $singleReturnPathListener = new SingleReturnPathListener();
        try {
            ParseTreeWalker::default()->walk($singleReturnPathListener, $tree);
            $singleReturnPathListener->exitTree();
        } catch (Throwable $t) {
            throw $this->client400BadContentExceptionFactory->createFromDetail($t->getMessage());
        }

        return $singleReturnPathListener;
    }

    public function validateQuery(string $cypherQuery): bool
    {
        $this->stopwatch->start(self::PROFILER_NAME);

        $redisKey = $this->redisKeyTypeFactory->getValidatedQueryCypherPathSubsetRedisKey($cypherQuery);
        if ($this->redisClient->exists((string) $redisKey)) {
            $this->stopwatch->stop(self::PROFILER_NAME);

            return true;
        }
        $this->logger->debug(
            'Unable to find cypher path subset query already validated and cached in Redis.',
            [
                'redisKey' => (string) $redisKey,
            ]
        );

        $this->analyseQuery($cypherQuery);

        $this->redisClient->set((string) $redisKey, true, 'EX', self::REDIS_QUERY_TTL_IN_SECONDS);

        $this->stopwatch->stop(self::PROFILER_NAME);

        return true;
    }
}
