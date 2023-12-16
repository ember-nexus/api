<?php

namespace App\Command;

use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use App\Type\TokenRevokeEntry;
use App\Type\TokenStateType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Safe\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

use function Safe\preg_match;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'token:revoke', description: 'Revoke tokens.')]
class TokenRevokeCommand extends Command
{
    private EmberNexusStyle $io;

    public const OPTION_FORCE = 'force';
    public const OPTION_DRY_RUN = 'dry-run';
    public const OPTION_LIST_ALL_AFFECTED_TOKENS = 'list-all-affected-tokens';
    public const OPTION_USER = 'user';
    public const OPTION_GROUP = 'group';
    public const OPTION_ISSUED_BEFORE = 'issued-before';
    public const OPTION_ISSUED_AFTER = 'issued-after';
    public const OPTION_ISSUED_WITHOUT_EXPIRATION_DATE = 'issued-without-expiration-date';
    public const OPTION_ISSUED_WITH_EXPIRATION_DATE = 'issued-with-expiration-date';
    public const NUMBER_OF_TOKENS_TO_DISPLAY = 10;
    public const TOKEN_REVOKE_BATCH_SIZE = 10;

    private bool $isForceActive = false;
    private bool $isDryRunActive = false;
    private bool $isListAllAffectedTokensActive = false;
    private ?UuidInterface $userUuid = null;
    private ?UuidInterface $groupUuid = null;
    private ?DateTime $issuedBefore = null;
    private ?DateTime $issuedAfter = null;
    private bool $isFilterIssuedWithoutExpirationDateActive = false;
    private bool $isFilterIssuedWithExpirationDateActive = false;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private AuthProvider $authProvider,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        // command options
        $this->addOption(
            self::OPTION_FORCE,
            'f',
            InputOption::VALUE_NEGATABLE,
            'If enabled, command will not ask for manual confirmation.',
            false
        );
        $this->addOption(
            self::OPTION_DRY_RUN,
            null,
            InputOption::VALUE_NEGATABLE,
            'Does not apply revocation. Also lists all tokens which would be affected.',
            false
        );
        $this->addOption(
            self::OPTION_LIST_ALL_AFFECTED_TOKENS,
            'l',
            InputOption::VALUE_NEGATABLE,
            'Lists all tokens.',
            false
        );
        // command filters
        $this->addOption(
            self::OPTION_USER,
            'u',
            InputOption::VALUE_REQUIRED,
            'Token revocation is only applied to an given user. User can be specified by its UUID (takes precedent) or its identifier.',
            null
        );
        $this->addOption(
            self::OPTION_GROUP,
            'g',
            InputOption::VALUE_REQUIRED,
            'Token revocation is only applied to users of given group. Group must be specified by its UUID.',
            null
        );
        $this->addOption(
            self::OPTION_ISSUED_BEFORE,
            'b',
            InputOption::VALUE_REQUIRED,
            'Token revocation is only applied to tokens issued before a given point in time. Datetime must be in the format "YYYY-MM-DD HH:MM" (UTC).',
            null
        );
        $this->addOption(
            self::OPTION_ISSUED_AFTER,
            'a',
            InputOption::VALUE_REQUIRED,
            'Token revocation is only applied to tokens issued after a given point in time. Datetime must be in the format "YYYY-MM-DD HH:MM" (UTC).',
            null
        );
        $this->addOption(
            self::OPTION_ISSUED_WITHOUT_EXPIRATION_DATE,
            null,
            InputOption::VALUE_NONE,
            'Token revocation is only applied to tokens with no explicit expiration date.'
        );
        $this->addOption(
            self::OPTION_ISSUED_WITH_EXPIRATION_DATE,
            null,
            InputOption::VALUE_NONE,
            'Token revocation is only applied to tokens with explicit expiration date set.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Token Revocation');

        $this->validateInput($input);
        $tokenRevokeEntries = $this->getAffectedTokenRevokeEntries();

        if (0 === count($tokenRevokeEntries)) {
            $this->io->finalMessage('No active tokens found.');

            return Command::SUCCESS;
        }

        $this->printAffectedTokens($tokenRevokeEntries);

        if (true === $input->getOption(self::OPTION_DRY_RUN)) {
            $this->io->finalMessage('Dry run finished.');

            return Command::SUCCESS;
        }

        if (true !== $this->isForceActive) {
            /**
             * @var QuestionHelper $helper
             */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(sprintf(
                '  Are you sure you want to revoke all %d tokens? [y/N]: ',
                count($tokenRevokeEntries)
            ), false);
            if (!$helper->ask($input, $output, $question)) {
                $this->io->finalMessage('Aborted revoking tokens.');

                return Command::FAILURE;
            }
            $this->io->newLine();
        }

        $this->io->writeln('  Revoking tokens...');

        $this->revokeTokens($tokenRevokeEntries);

        $this->io->finalMessage('Successfully revoked tokens.');

        return Command::SUCCESS;
    }

    private function validateInput(InputInterface $input): void
    {
        $this->isForceActive = $input->getOption(self::OPTION_FORCE);
        $this->isDryRunActive = $input->getOption(self::OPTION_DRY_RUN);
        $this->isListAllAffectedTokensActive = $input->getOption(self::OPTION_LIST_ALL_AFFECTED_TOKENS);

        $this->isFilterIssuedWithExpirationDateActive = $input->getOption(self::OPTION_ISSUED_WITH_EXPIRATION_DATE);
        $this->isFilterIssuedWithoutExpirationDateActive = $input->getOption(self::OPTION_ISSUED_WITHOUT_EXPIRATION_DATE);

        if ($this->isFilterIssuedWithExpirationDateActive && $this->isFilterIssuedWithoutExpirationDateActive) {
            throw new Exception(sprintf('Using both %s and %s is not possible.', self::OPTION_ISSUED_WITH_EXPIRATION_DATE, self::OPTION_ISSUED_WITHOUT_EXPIRATION_DATE));
        }

        if (null !== $input->getOption(self::OPTION_ISSUED_BEFORE)) {
            $this->issuedBefore = DateTime::createFromFormat('Y-m-d H:i', $input->getOption(self::OPTION_ISSUED_BEFORE));
        }

        if (null !== $input->getOption(self::OPTION_ISSUED_AFTER)) {
            $this->issuedAfter = DateTime::createFromFormat('Y-m-d H:i', $input->getOption(self::OPTION_ISSUED_AFTER));
        }

        if ($this->issuedBefore && $this->issuedAfter) {
            if ($this->issuedBefore->getTimestamp() < $this->issuedAfter->getTimestamp()) {
                throw new Exception(sprintf('%s can not be before %s.', self::OPTION_ISSUED_BEFORE, self::OPTION_ISSUED_AFTER));
            }
        }

        if (null !== $input->getOption(self::OPTION_USER)) {
            $userIdentifier = $input->getOption(self::OPTION_USER);
            if (preg_match(Regex::UUID_V4, $userIdentifier)) {
                $this->userUuid = Uuid::fromString($userIdentifier);
            } else {
                $res = $this->cypherEntityManager->getClient()->runStatement(new Statement(
                    sprintf(
                        'MATCH (u:User {%s: $userIdentifier}) RETURN u.id LIMIT 1',
                        $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
                    ),
                    [
                        'userIdentifier' => $userIdentifier,
                    ]
                ));

                if (1 !== count($res)) {
                    throw new Exception(sprintf('Unable to find user identified by %s.', $userIdentifier));
                }

                $this->userUuid = Uuid::fromString($res[0]['u.id']);
            }
        }

        if (null !== $input->getOption(self::OPTION_GROUP)) {
            $this->groupUuid = Uuid::fromString($input->getOption(self::OPTION_GROUP));
        }
    }

    private function buildGetAffectedTokenRevokeEntriesQuery(): Statement
    {
        $filters = [];
        $arguments = [];

        if ($this->isFilterIssuedWithExpirationDateActive) {
            $filters[] = 't.expirationDate IS NOT NULL';
        }
        if ($this->isFilterIssuedWithoutExpirationDateActive) {
            $filters[] = 't.expirationDate IS NULL';
        }

        if ($this->issuedBefore) {
            $filters[] = 't.created < $issuedBefore';
            $arguments['issuedBefore'] = $this->issuedBefore;
        }

        if ($this->issuedAfter) {
            $filters[] = 't.created > $issuedAfter';
            $arguments['issuedAfter'] = $this->issuedAfter;
        }

        if ($this->userUuid) {
            $filters[] = 'u.id = $userIdentifier';
            $arguments['userIdentifier'] = $this->userUuid->toString();
        }

        if ($this->groupUuid) {
            $filters[] = '(t)<-[:OWNS]-(:User)-[:IS_IN_GROUP*1..]->(:Group {id: $groupIdentifier})';
            $arguments['groupIdentifier'] = $this->groupUuid->toString();
        }

        $joinedFilters = join("\nAND ", $filters);
        $finalQuery = sprintf(
            "MATCH (t:Token {state: '%s'})<-[:OWNS]-(u:User)\n".
            '%s%s%s'.
            "RETURN t.id, t.created, t.expirationDate, t.hash, u.id, u.%s as userUniqueIdentifier\n".
            'ORDER BY t.created ASC, t.id ASC',
            TokenStateType::ACTIVE->value,
            count($filters) > 0 ? 'WHERE ' : '',
            $joinedFilters,
            count($filters) > 0 ? "\n" : '',
            $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
        );

        return new Statement($finalQuery, $arguments);
    }

    /**
     * @return TokenRevokeEntry[]
     */
    private function getAffectedTokenRevokeEntries(): array
    {
        $queryResultLines = $this->cypherEntityManager->getClient()->runStatement(
            $this->buildGetAffectedTokenRevokeEntriesQuery()
        );
        $result = [];
        foreach ($queryResultLines as $queryResultLine) {
            $tokenCreated = $queryResultLine['t.created'];
            $expirationDate = $queryResultLine['t.expirationDate'];
            /**
             * @var DateTimeZoneId  $tokenCreated
             * @var ?DateTimeZoneId $expirationDate
             */
            $result[] = new TokenRevokeEntry(
                Uuid::fromString($queryResultLine['t.id']),
                $tokenCreated->toDateTime(),
                $expirationDate?->toDateTime(),
                Uuid::fromString($queryResultLine['u.id']),
                $queryResultLine['userUniqueIdentifier'],
                $queryResultLine['t.hash']
            );
        }

        return $result;
    }

    /**
     * @param TokenRevokeEntry[] $tokenRevokeEntries
     */
    private function printAffectedTokens(array $tokenRevokeEntries): void
    {
        $countTokenRevokeEntries = count($tokenRevokeEntries);

        $showOnlyFirstTokens = $countTokenRevokeEntries > self::NUMBER_OF_TOKENS_TO_DISPLAY
            && false === $this->isListAllAffectedTokensActive
            && false === $this->isDryRunActive;

        if (true === $this->isDryRunActive) {
            $this->io->writeln(sprintf(
                '  %d tokens would be affected by revocation:',
                $countTokenRevokeEntries
            ));
        } else {
            if ($showOnlyFirstTokens) {
                $this->io->writeln(sprintf(
                    '  %d tokens are affected by revocation. First %s are shown:',
                    $countTokenRevokeEntries,
                    self::NUMBER_OF_TOKENS_TO_DISPLAY
                ));
            } else {
                $this->io->writeln(sprintf(
                    '  %d tokens are affected by revocation:',
                    $countTokenRevokeEntries
                ));
            }
        }
        $this->io->newLine();

        $rows = [];
        foreach ($tokenRevokeEntries as $tokenRevokeEntry) {
            $rows[] = [
                $tokenRevokeEntry->getTokenId()->toString(),
                $tokenRevokeEntry->getUserId()->toString(),
                $tokenRevokeEntry->getUserUniqueIdentifier(),
                $tokenRevokeEntry->getTokenCreated()->format('Y-m-d H:i:s'),
                $tokenRevokeEntry->getTokenExpirationDate()?->format('Y-m-d H:i:s') ?? '-',
            ];
            if (count($rows) >= self::NUMBER_OF_TOKENS_TO_DISPLAY && $showOnlyFirstTokens) {
                break;
            }
        }

        $table = $this->io->createCompactTable();
        $table->setHeaders([
            'Token UUID',
            'User UUID',
            'User identifier',
            'Created',
            'Expires on',
        ]);
        $table->setRows($rows);
        $table->render();
        $this->io->newLine();
    }

    /**
     * @param TokenRevokeEntry[] $tokenRevokeEntries
     */
    private function revokeTokens(array $tokenRevokeEntries): void
    {
        $countTokenRevokeEntries = count($tokenRevokeEntries);
        $progressBar = $this->io->createProgressBarInInteractiveTerminal($countTokenRevokeEntries);
        $progressBar?->display();

        $currentItem = 1;
        foreach ($tokenRevokeEntries as $tokenRevokeEntry) {
            ++$currentItem;
            $tokenElement = $this->elementManager->getNode($tokenRevokeEntry->getTokenId());
            if (!$tokenElement) {
                $message = sprintf(
                    'Unable to revoke token with UUID %s from user with UUID %s, as token could not be fetched from database.',
                    $tokenRevokeEntry->getTokenId()->toString(),
                    $tokenRevokeEntry->getUserId()->toString()
                );
                $this->io->writeln($message);
                $this->logger->notice($message);
                continue;
            }
            $tokenElement->addProperty('state', TokenStateType::REVOKED->value);
            $this->elementManager->merge($tokenElement);

            $this->redisClient->expire(
                $this->authProvider->getRedisTokenKeyFromHashedToken($tokenRevokeEntry->getTokenHash()),
                0
            );

            if (0 === $currentItem % self::TOKEN_REVOKE_BATCH_SIZE) {
                $this->elementManager->flush();
                $progressBar?->setProgress($currentItem);
            }
        }
        $this->elementManager->flush();
        $progressBar?->finish();
        $progressBar?->clear();
        $this->io->newLine();
    }
}
