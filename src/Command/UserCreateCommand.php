<?php

namespace App\Command;

use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use App\Type\NodeElement;
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

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'user:create', description: 'Creates a new user.')]
class UserCreateCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private UserPasswordHasher $userPasswordHasher,
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
                'Identifier of the user, %s, must be unique',
                $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
            )
        );
        $this->addArgument('password', InputArgument::REQUIRED, 'Password of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('User Create');

        $identifier = $input->getArgument('identifier');

        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    'MATCH (user:User {%s: $uniqueIdentifierValue}) RETURN user',
                    $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
                ),
                [
                    'uniqueIdentifierValue' => $identifier,
                ]
            )
        );
        if ($res->count() > 0) {
            $this->io->finalMessage(sprintf(
                "Unable to create new user with %s '%s', because %s is already in use.",
                $this->emberNexusConfiguration->getRegisterUniqueIdentifier(),
                $identifier,
                $this->emberNexusConfiguration->getRegisterUniqueIdentifier()
            ));

            return self::FAILURE;
        }

        $userId = Uuid::uuid4();

        $userNode = (new NodeElement())
            ->setLabel('User')
            ->addProperties([
                $this->emberNexusConfiguration->getRegisterUniqueIdentifier() => $identifier,
                '_passwordHash' => $this->userPasswordHasher->hashPassword($input->getArgument('password')),
            ])
            ->setIdentifier($userId);

        $this->elementManager
            ->create($userNode)
            ->flush();

        $this->io->finalMessage(sprintf(
            "Created user with %s '<info>%s</info>' successfully, UUID is <info>%s</info>.",
            $this->emberNexusConfiguration->getRegisterUniqueIdentifier(),
            $identifier,
            $userId->toString()
        ));

        return Command::SUCCESS;
    }
}
