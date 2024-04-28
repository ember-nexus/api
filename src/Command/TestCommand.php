<?php

declare(strict_types=1);

namespace App\Command;

use App\Style\EmberNexusStyle;
use AsyncAws\S3\S3Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'test', description: 'Test')]
class TestCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private S3Client $s3Client
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Test');

        $buckets = $this->s3Client->listBuckets();
        print_r($buckets->getBuckets());

        $res = $this->s3Client->bucketExists(['Bucket' => 'api-upload2']);
        print_r($res->getState());
        print_r($res->isSuccess() ? 'true' : 'false');

        return Command::SUCCESS;
    }
}
