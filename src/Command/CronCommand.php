<?php

declare(strict_types=1);

namespace App\Command;

use App\Style\EmberNexusStyle;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron', description: 'Executes background tasks')]
class CronCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private ParameterBagInterface $bag,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Cron');

        $this->io->writeln('This command is currently a placeholder.');
        $this->io->newLine();

        $isCronDisabled = $this->bag->get('isCronDisabled');
        if (!is_bool($isCronDisabled)) {
            throw new LogicException(sprintf('Expected "isCronDisabled" to be of type boolean, got %s.', get_debug_type($isCronDisabled)));
        }
        if ($isCronDisabled) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        $this->io->finalMessage('Finished.');

        return Command::SUCCESS;
    }
}
