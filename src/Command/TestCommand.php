<?php

namespace App\Command;

use App\Security\AccessChecker;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test', description: 'Internal test command - do not use in production.', hidden: true)]
class TestCommand extends Command
{
    public function __construct(
        private AccessChecker $accessChecker
    ) {
        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dataUuid = UuidV4::fromString('3a3c2f8b-d1bd-40fd-b381-82de60539c9f');

        $uuids = $this->accessChecker->getDirectGroupsWithAccessToElement($dataUuid, AccessType::DELETE);
        foreach ($uuids as $uuid) {
            $output->writeln(sprintf(
                'Group with UUID %s has access to data node.',
                $uuid->toString()
            ));
        }
        $output->writeln('');

        $uuids = $this->accessChecker->getDirectUsersWithAccessToElement($dataUuid, AccessType::DELETE);
        foreach ($uuids as $uuid) {
            $output->writeln(sprintf(
                'User with UUID %s has access to data node.',
                $uuid->toString()
            ));
        }

        return Command::SUCCESS;
    }
}
