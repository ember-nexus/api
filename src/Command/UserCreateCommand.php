<?php

namespace App\Command;

use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use App\Type\NodeElement;
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
        private UserPasswordHasher $userPasswordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Name of the user, must be unique');
        // TODO: Make plaintext password insert optional, direct insert from console would be better
        $this->addArgument('password', InputArgument::REQUIRED, 'Password of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $username = $input->getArgument('username');

        // check if username already exists

        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (user:User {username: $username}) RETURN user',
                [
                    'username' => $username,
                ]
            )
        );
        if ($res->count() > 0) {
            $this->io->writeln(sprintf(
                "Unable to create new user with username '%s', because username is already in use.",
                $username
            ));

            return self::FAILURE;
        }

        $userId = Uuid::uuid4();

        $userNode = (new NodeElement())
            ->setLabel('User')
            ->addProperties([
                'username' => $username,
                '_passwordHash' => $this->userPasswordHasher->hashPassword($input->getArgument('password')),
            ])
            ->setIdentifier($userId);

        $this->elementManager
            ->create($userNode)
            ->flush();

        $this->io->writeln(sprintf(
            "Created user '%s' successfully, UUID is %s",
            $username,
            $userId->toString()
        ));

        return Command::SUCCESS;
    }
}
