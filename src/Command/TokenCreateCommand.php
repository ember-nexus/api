<?php

namespace App\Command;

use App\Helper\Regex;
use App\Security\TokenGenerator;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use EmberNexusBundle\Service\EmberNexusConfiguration;
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
#[AsCommand(name: 'token:create', description: 'Creates a new token for an user.')]
class TokenCreateCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private TokenGenerator $tokenGenerator,
        private EmberNexusConfiguration $emberNexusConfiguration
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'identifier',
            InputArgument::REQUIRED,
            sprintf(
                'Unique identifier of the user (%s) or the user\'s UUID',
                $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
            )
        );
        $this->addArgument('password', InputArgument::REQUIRED, 'Password of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Token Create');

        $identifier = $input->getArgument('identifier');

        if (preg_match(Regex::UUID_V4, $identifier)) {
            $identifier = Uuid::fromString($identifier);
        } else {
            $res = $this->cypherEntityManager->getClient()->runStatement(
                Statement::create(
                    sprintf(
                        'MATCH (user:User {%s: $uniqueIdentifierValue}) RETURN user.id as id',
                        $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
                    ),
                    [
                        'uniqueIdentifierValue' => $identifier,
                    ]
                )
            );
            if (0 === $res->count()) {
                $this->io->finalMessage(sprintf(
                    "Unable to find user with %s '%s'.",
                    $this->emberNexusConfiguration->getRegisterUniqueIdentifier(),
                    $identifier
                ));

                return self::FAILURE;
            }
            if ($res->count() > 1) {
                $this->io->finalMessage(sprintf(
                    "Unexpectedly found multiple users for %s '%s'.",
                    $this->emberNexusConfiguration->getRegisterUniqueIdentifier(),
                    $identifier
                ));

                return self::FAILURE;
            }
            $identifier = Uuid::fromString($res->first()->get('id'));
        }

        $user = $this->elementManager->getNode($identifier);

        if (!$user) {
            $this->io->finalMessage(sprintf(
                "Error: Unable to find user with id '%s'.",
                $identifier->toString()
            ));

            return self::FAILURE;
        }

        $this->io->writeln(
            sprintf(
                "  Found user with identifier '<info>%s</info>', user's UUID is <info>%s</info>.",
                $input->getArgument('identifier'),
                $identifier->toString()
            )
        );

        $this->io->newLine();

        if (!$user->hasProperty('_passwordHash')) {
            $this->io->finalMessage(sprintf(
                "Error: User with id '%s' does not have property _passwordHash set, which is required for login.",
                $identifier->toString()
            ));

            return self::FAILURE;
        }

        if (!password_verify($input->getArgument('password'), $user->getProperty('_passwordHash'))) {
            $this->io->finalMessage(sprintf(
                "Error: Provided password for user with id '%s' is incorrect, unable to login.",
                $identifier->toString()
            ));

            return self::FAILURE;
        }

        $token = $this->tokenGenerator->createNewToken($identifier);

        $this->io->finalMessage(
            sprintf(
                'Successfully created new token: <info>%s</info> .',
                $token
            )
        );

        return Command::SUCCESS;
    }
}
