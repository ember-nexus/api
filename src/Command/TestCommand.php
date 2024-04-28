<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\LockService;
use App\Style\EmberNexusStyle;
use App\Type\Lock\FileUploadCheckLock;
use AsyncAws\S3\S3Client;
use Ramsey\Uuid\Uuid;
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
        private S3Client $s3Client,
        private LockService $lockService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Test');

        //        $buckets = $this->s3Client->listBuckets();
        //        print_r($buckets->getBuckets());
        //
        //        $res = $this->s3Client->bucketExists(['Bucket' => 'api-upload2']);
        //        print_r($res->getState());
        //        print_r($res->isSuccess() ? 'true' : 'false');

        $lock = new FileUploadCheckLock(
            Uuid::fromString('119bc6f4-a9cd-4d6a-bbaf-768061542ef9'),
            Uuid::fromString('21850a04-ac44-4b14-a64d-6d498e4134a9'),
        );

        $this->lockService->acquireLock($lock);
        $this->lockService->releaseLock($lock);

        return Command::SUCCESS;
    }
}
