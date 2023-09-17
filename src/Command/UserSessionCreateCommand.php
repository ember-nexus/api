<?php

namespace App\Command;

use App\Helper\Regex;
use App\Security\TokenGenerator;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

use function Safe\preg_match;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'user:session:create', description: 'Creates a new session for an user.')]
class UserSessionCreateCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private TokenGenerator $tokenGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('identifier', InputArgument::REQUIRED, 'Username of UUID of the user');
        $this->addArgument('password', InputArgument::REQUIRED, 'Password of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $identifier = $input->getArgument('identifier');

        if (preg_match(Regex::UUID_V4, $identifier)) {
            $identifier = Uuid::fromString($identifier);
        } else {
            $res = $this->cypherEntityManager->getClient()->runStatement(
                Statement::create(
                    'MATCH (user:User {username: $username}) RETURN user.id',
                    [
                        'username' => $identifier,
                    ]
                )
            );
            if (0 === $res->count()) {
                $this->io->writeln(sprintf(
                    "Unable to find user with username '%s'.",
                    $identifier
                ));

                return self::FAILURE;
            }
            $identifier = Uuid::fromString($res->first()->get('user.id'));
        }

        $user = $this->elementManager->getNode($identifier);

        if (!$user) {
            $this->io->writeln(sprintf(
                "Error: Unable to find user with id '%s'.",
                $identifier->toString()
            ));

            return self::FAILURE;
        }

        if (!$user->hasProperty('_passwordHash')) {
            $this->io->writeln(sprintf(
                "Error: User with id '%s' does not have property _passwordHash set, which is required for login.",
                $identifier->toString()
            ));

            return self::FAILURE;
        }

        if (!password_verify($input->getArgument('password'), $user->getProperty('_passwordHash'))) {
            $this->io->writeln(sprintf(
                "Error: Provided password for user with id '%s' is incorrect, unable to login.",
                $identifier->toString()
            ));

            return self::FAILURE;
        }

        $sessionToken = $this->tokenGenerator->createNewToken($identifier);

        $this->io->writeln(sprintf(
            "Successfully created new session for user with id '%s'.\nSession token is: %s",
            $identifier->toString(),
            $sessionToken
        ));

        return Command::SUCCESS;
    }
}
