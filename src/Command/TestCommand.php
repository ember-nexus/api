<?php

namespace App\Command;

use App\Service\EtagService;
use App\Style\EmberNexusStyle;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuupola\Base58;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    private EmberNexusStyle $io;

    private Base58 $encoder;

    public function __construct(
        private EtagService $etagService
    ) {
        parent::__construct();
        $this->encoder = new Base58();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Test');

        $etag = $this->etagService->getEtagForElementId(Uuid::fromString('8693498b-b58d-4210-b8ea-41c5a9adbe5f'));
        $this->io->writeln($etag);

        $childCollectionEtag = $this->etagService->getEtagForChildrenCollection(Uuid::fromString('7b80b203-2b82-40f5-accd-c7089fe6114e'));
        $this->io->writeln($childCollectionEtag);

        return Command::SUCCESS;
    }
}
