<?php

namespace App\Command;

use App\Security\TokenGenerator;
use League\Flysystem\FilesystemOperator;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test3')]
class Test3Command extends Command
{
    public function __construct(
        private TokenGenerator $tokenGenerator,
        private FilesystemOperator $backupStorage,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //        $token = $this->tokenGenerator->createNewToken(UuidV4::fromString('6ce3006b-6b7f-4770-8075-d2bf91804d14'));
        //        $output->writeln($token);

        $combinedJSON = [];
        $files = $this->backupStorage->listContents('manual/node/', true);
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $combinedJSON[] = \Safe\json_encode(\Safe\json_decode($this->backupStorage->read($file->path()), true), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $combinedJSON = implode("\n", $combinedJSON);
        $this->backupStorage->write('/nodes.combined.jsonl', $combinedJSON);

        return Command::SUCCESS;
    }
}
