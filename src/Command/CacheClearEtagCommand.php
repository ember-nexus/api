<?php

declare(strict_types=1);

namespace App\Command;

use App\Style\EmberNexusStyle;
use Exception;
use Predis\Client as RedisClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cache:clear:etag', description: 'Removes all cached etag values from Redis.')]
class CacheClearEtagCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private RedisClient $redisClient,
    ) {
        parent::__construct();
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Cache Clear Etag');

        $this->io->writeln('  Expiring Etags iteratively...');
        $this->io->write('  ');

        $cursor = 0;
        $cursorIsFinished = false;
        $count = 0;
        while (!$cursorIsFinished) {
            $res = $this->redisClient->scan($cursor, ['MATCH' => 'etag:*', 'COUNT' => 100]);
            $cursor = intval($res[0]);
            $keys = $res[1];
            if (!is_array($keys)) {
                throw new Exception(sprintf('Expected Redis keys to be array, got %s.', get_class($keys)));
            }
            if (0 == $cursor) {
                $cursorIsFinished = true;
            }
            foreach ($keys as $key) {
                $this->redisClient->expire($key, 0);
                ++$count;
            }
            $this->io->write('.');
        }
        $this->io->newLine();
        $this->io->writeln(sprintf(
            '  Expired %d etags.',
            $count
        ));
        $this->io->newLine();

        $this->io->finalMessage('Successfully expired all Etags.');

        return Command::SUCCESS;
    }
}
