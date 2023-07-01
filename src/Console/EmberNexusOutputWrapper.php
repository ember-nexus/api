<?php

namespace App\Console;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmberNexusOutputWrapper implements OutputInterface
{
    public function __construct(
        private OutputInterface $output
    ) {
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function write(iterable|string $messages, bool $newline = false, int $options = 0): void
    {
        $this->output->write($messages, $newline, $options);
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function writeln(iterable|string $messages, int $options = 0): void
    {
        if (is_string($messages)) {
            $this->output->writeln('  '.$messages, $options);
        } else {
            $this->output->writeln($messages, $options);
        }
    }

    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }
}
