<?php

namespace App\Command;

use App\Service\EtagService;
use App\Style\EmberNexusStyle;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Tuupola\Base58;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    private OutputStyle $io;

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

        $input = [
            Uuid::uuid4()->getBytes(),
            (new DateTime())->getTimestamp(),
        ];

        $hashFunction = hash_init('xxh3');

        foreach ($input as $item) {
            hash_update($hashFunction, $item);
        }

        $hashResult = hash_final($hashFunction, true);
        $encodedResult = $this->encoder->encode($hashResult);
        $this->io->writeln($encodedResult);

        $hashFunction = hash_init('xxh3');

        for ($i = 0; $i < 1000; ++$i) {
            hash_update($hashFunction, Uuid::uuid4()->getBytes());
            hash_update($hashFunction, (new DateTime())->getTimestamp());
        }

        $hashResult = hash_final($hashFunction, true);
        $encodedResult = $this->encoder->encode($hashResult);
        $this->io->writeln($encodedResult);

        $this->io->writeln('-------');

        $etag = $this->etagService->getEtagForElementId(Uuid::fromString('8693498b-b58d-4210-b8ea-41c5a9adbe5f'));

        $this->io->writeln($etag);

        return Command::SUCCESS;
    }
}
