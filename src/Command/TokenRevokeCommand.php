<?php

namespace App\Command;

use App\Helper\Regex;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use App\Type\TokenStateType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\OutputStyle;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

use function Safe\preg_match;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'token:revoke', description: 'Revoke tokens.')]
class TokenRevokeCommand extends Command
{
    private OutputStyle $io;

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

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private EmberNexusConfiguration $emberNexusConfiguration,
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

        $filters = [];
        $arguments = [];

        if (
            true === $input->getOption(self::OPTION_ISSUED_WITH_EXPIRATION_DATE)
            && true === $input->getOption(self::OPTION_ISSUED_WITHOUT_EXPIRATION_DATE)
        ) {
            throw new Exception(sprintf('Using both %s and %s is not possible.', self::OPTION_ISSUED_WITH_EXPIRATION_DATE, self::OPTION_ISSUED_WITHOUT_EXPIRATION_DATE));
        }
        if (true === $input->getOption(self::OPTION_ISSUED_WITH_EXPIRATION_DATE)) {
            $filters[] = 't.expirationDate IS NOT NULL';
        }
        if (true === $input->getOption(self::OPTION_ISSUED_WITHOUT_EXPIRATION_DATE)) {
            $filters[] = 't.expirationDate IS NULL';
        }

        $optionIssuedBefore = null;
        if (null !== $input->getOption(self::OPTION_ISSUED_BEFORE)) {
            $optionIssuedBefore = DateTime::createFromFormat('Y-m-d H:i', $input->getOption(self::OPTION_ISSUED_BEFORE));
            $filters[] = 't.created < $issuedBefore';
            $arguments['issuedBefore'] = $optionIssuedBefore;
        }

        $optionIssuedAfter = null;
        if (null !== $input->getOption(self::OPTION_ISSUED_AFTER)) {
            $optionIssuedAfter = DateTime::createFromFormat('Y-m-d H:i', $input->getOption(self::OPTION_ISSUED_AFTER));
            $filters[] = 't.created > $issuedAfter';
            $arguments['issuedAfter'] = $optionIssuedAfter;
        }

        if ($optionIssuedBefore && $optionIssuedAfter) {
            if ($optionIssuedBefore->getTimestamp() < $optionIssuedAfter->getTimestamp()) {
                throw new Exception(sprintf('%s can not be before %s.', self::OPTION_ISSUED_BEFORE, self::OPTION_ISSUED_AFTER));
            }
        }

        if (null !== $input->getOption(self::OPTION_USER)) {
            $userIdentifier = $input->getOption(self::OPTION_USER);
            if (preg_match(Regex::UUID_V4, $userIdentifier)) {
                $filters[] = 'u.id = $userIdentifier';
            } else {
                $filters[] = sprintf(
                    'u.%s = $userIdentifier',
                    $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
                );
            }
            $arguments['userIdentifier'] = $userIdentifier;
        }

        if (null !== $input->getOption(self::OPTION_GROUP)) {
            $filters[] = '(t)<-[:OWNS]-(:User)-[:IS_IN_GROUP*1..]->(:Group {id: $groupIdentifier})';
            $arguments['groupIdentifier'] = $input->getOption(self::OPTION_GROUP);
        }

        $joinedFilters = join("\nAND ", $filters);
        $finalQuery = sprintf(
            "MATCH (t:Token {state: '%s'})<-[:OWNS]-(u:User)\n".
            '%s%s%s'.
            "RETURN t.id, t.created, t.expirationDate, u.id, u.%s as userUniqueIdentifier\n".
            'ORDER BY t.created ASC, t.id ASC',
            TokenStateType::ACTIVE->value,
            count($filters) > 0 ? 'WHERE ' : '',
            $joinedFilters,
            count($filters) > 0 ? "\n" : '',
            $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
        );

        $res = $this->cypherEntityManager->getClient()->runStatement(
            new Statement($finalQuery, $arguments)
        );

        $this->io->isDecorated();

        $countTokensToBeRevoked = count($res);
        if (0 === $countTokensToBeRevoked) {
            $this->io->finalMessage('No active tokens found.');

            return Command::SUCCESS;
        }

        $showOnlyFirstTokens = $countTokensToBeRevoked > self::NUMBER_OF_TOKENS_TO_DISPLAY
            && false === $input->getOption(self::OPTION_LIST_ALL_AFFECTED_TOKENS)
            && false === $input->getOption(self::OPTION_DRY_RUN);

        if (true === $input->getOption(self::OPTION_DRY_RUN)) {
            $this->io->writeln(sprintf(
                '  %d tokens would be affected by revocation:',
                $countTokensToBeRevoked
            ));
        } else {
            if ($showOnlyFirstTokens) {
                $this->io->writeln(sprintf(
                    '  %d tokens are affected by revocation. First %s are shown:',
                    $countTokensToBeRevoked,
                    self::NUMBER_OF_TOKENS_TO_DISPLAY
                ));
            } else {
                $this->io->writeln(sprintf(
                    '  %d tokens are affected by revocation:',
                    $countTokensToBeRevoked
                ));
            }
        }
        $this->io->newLine();

        $rows = [];
        foreach ($res as $tokenResult) {
            $tokenCreated = $tokenResult['t.created'];
            if ($tokenCreated instanceof DateTimeZoneId) {
                $tokenCreated = $tokenCreated->toDateTime()->format('Y-m-d H:i:s');
            }
            $tokenExpires = $tokenResult['t.expirationDate'];
            if ($tokenExpires instanceof DateTimeZoneId) {
                $tokenExpires = $tokenExpires->toDateTime()->format('Y-m-d H:i:s');
            }
            $rows[] = [
                $tokenResult['t.id'],
                $tokenResult['u.id'],
                $tokenResult['userUniqueIdentifier'],
                $tokenCreated,
                $tokenExpires ?? '-',
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

        if (true === $input->getOption(self::OPTION_DRY_RUN)) {
            $this->io->finalMessage('Dry run finished.');

            return self::SUCCESS;
        }

        if (true !== $input->getOption(self::OPTION_FORCE)) {
            /**
             * @var QuestionHelper $helper
             */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(sprintf(
                '  Are you sure you want to revoke all %d tokens? [y/N]: ',
                $countTokensToBeRevoked
            ), false);
            if (!$helper->ask($input, $output, $question)) {
                $this->io->finalMessage('Aborted revoking tokens.');

                return self::FAILURE;
            }
            $this->io->newLine();
        }

        $this->io->writeln('  Revoking tokens...');

        $progressBar = $this->io->createProgressBarInInteractiveTerminal($countTokensToBeRevoked);
        $progressBar?->display();

        foreach ($res as $i => $tokenResult) {
            $tokenUuid = Uuid::fromString($tokenResult['t.id']);
            $tokenElement = $this->elementManager->getNode($tokenUuid);
            if (!$tokenElement) {
                $message = sprintf(
                    'Unable to revoke token with UUID %s from user with UUID %s, as token could not be fetched from database.',
                    $tokenResult['t.id'],
                    $tokenResult['u.id']
                );
                $this->io->writeln($message);
                $this->logger->notice($message);
                continue;
            }
            $tokenElement->addProperty('state', TokenStateType::REVOKED->value);
            $this->elementManager->merge($tokenElement);
            if (0 === $i % self::TOKEN_REVOKE_BATCH_SIZE) {
                $this->elementManager->flush();
                $progressBar?->setProgress($i + 1);
            }
        }
        $this->elementManager->flush();
        $progressBar?->finish();
        $progressBar?->clear();
        $this->io->newLine();

        $this->io->finalMessage('Successfully revoked tokens.');

        return Command::SUCCESS;
    }
}
