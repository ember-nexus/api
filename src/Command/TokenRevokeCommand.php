<?php

namespace App\Command;

use App\Factory\Exception\Client400IncompleteMutualDependencyExceptionFactory;
use App\Helper\Regex;
use App\Security\TokenGenerator;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
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
    public const OPTION_USER = 'user';
    public const OPTION_GROUP = 'group';
    public const OPTION_ISSUED_BEFORE = 'issued-before';
    public const OPTION_ISSUED_AFTER = 'issued-after';
    public const OPTION_ISSUED_WITHOUT_EXPIRATION_DATE = 'issued-without-expiration-date';
    public const OPTION_ISSUED_WITH_EXPIRATION_DATE = 'issued-with-expiration-date';

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private TokenGenerator $tokenGenerator,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Client400IncompleteMutualDependencyExceptionFactory $client400IncompleteMutualDependencyExceptionFactory
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
            'Lists tokens which would be affected by revocation. Does not apply said revocation.',
            false
        );
        // command filters
        $this->addOption(
            self::OPTION_USER,
            'u',
            InputOption::VALUE_REQUIRED,
            'Token revocation is only applied to given user. User can be specified by its UUID (takes precedent) or its identifier.',
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
            'Token revocation is only applied to tokens issued before a given datetime. Datetime must be in the format "YYYY-MM-DD HH:MM" (UTC).',
            null
        );
        $this->addOption(
            self::OPTION_ISSUED_AFTER,
            'a',
            InputOption::VALUE_REQUIRED,
            'Token revocation is only applied to tokens issued after a given datetime. Datetime must be in the format "YYYY-MM-DD HH:MM" (UTC).',
            null
        );
        $this->addOption(
            self::OPTION_ISSUED_WITHOUT_EXPIRATION_DATE,
            null,
            InputOption::VALUE_NEGATABLE,
            'Token revocation is only applied to tokens with no explicit expiration date.',
            false
        );
        $this->addOption(
            self::OPTION_ISSUED_WITH_EXPIRATION_DATE,
            null,
            InputOption::VALUE_NEGATABLE,
            'Token revocation is only applied to tokens with explicit expiration date set.',
            false
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
            "MATCH (t:Token)<-[:OWNS]-(u:User)\n".
            '%s%s%s'.
            "RETURN t.id, t.created, t.expirationDate, u.id, u.%s as userUniqueIdentifier\n".
            'ORDER BY t.created ASC, t.id ASC',
            count($filters) > 0 ? 'WHERE ' : '',
            $joinedFilters,
            count($filters) > 0 ? "\n" : '',
            $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
        );

        //        $this->io->writeln("----------------------------");
        //
        //        foreach ($filters as $filter) {
        //            $this->io->writeln('  '.$filter);
        //        }
        //
        //        $this->io->writeln("----------------------------");

        $res = $this->cypherEntityManager->getClient()->runStatement(
            new Statement($finalQuery, $arguments)
        );

        $countTokensToBeRevoked = count($res);
        if (0 === $countTokensToBeRevoked) {
            $this->io->finalMessage('No tokens found.');

            return Command::SUCCESS;
        }

        $this->io->writeln(sprintf(
            '  %d tokens are affected of revocation%s',
            $countTokensToBeRevoked,
            $countTokensToBeRevoked > 10 ? '. First 10 are shown:' : ':'
        ));
        $this->io->newLine();

        $rows = [];
        foreach ($res as $i => $tokenResult) {
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
            if (count($rows) >= 10) {
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

        //        foreach ($res as $tokenResult) {
        //            $this->io->writeln(sprintf(
        //                "Token: %s, owned by %s",
        //                $tokenResult['t.id'],
        //                $tokenResult['userUniqueIdentifier']
        //            ));
        //        }

        //        $normalizedArguments = [];
        //        foreach ($arguments as $i => $argument) {
        //            if ($argument instanceof \DateTimeInterface) {
        //                $argument = 'datetime("'.$argument->format('c').'")';
        //            } else {
        //                $argument = \Safe\json_encode($argument, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        //            }
        //            $normalizedArguments[] = sprintf(
        //                "    %s: %s,",
        //                $i,
        //                $argument
        //            );
        //        }
        //
        //        $finalArguments = sprintf(
        //            ":params\n{\n%s\n}",
        //            join("\n", $normalizedArguments)
        //        );
        //
        //        $this->io->writeln($finalArguments);
        //        $this->io->newLine();
        //        $this->io->writeln($finalQuery);

        $this->io->finalMessage('Successfully revoked tokens.');

        return Command::SUCCESS;
    }
}
