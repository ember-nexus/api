<?php

namespace App\Command;

use App\Security\AccessChecker;
use App\Service\RecheckSearchAccessService;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test')]
class TestCommand extends Command
{
    public function __construct(
        private RecheckSearchAccessService $recheckSearchAccessService,
        private AccessChecker $accessChecker
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //        $uuid = UuidV4::uuid4();
        //        $this->recheckSearchAccessService->markElementToBeCheckedInFuture($uuid);
        //        $output->writeln(sprintf(
        //            "Marked element with uuid %s for search recheck.",
        //            $uuid->toString()
        //        ));
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
