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

        $this->redisClient->set((string) $redisKey, true, 'EX', self::REDIS_QUERY_TTL_IN_SECONDS);

        $this->stopwatch->stop(self::PROFILER_NAME);

        return true;
    }
}
