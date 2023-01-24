<?php

namespace App\Style;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

class EonStyle extends SymfonyStyle
{
    private int $lineLength = 120;

    public function __construct(
        private InputInterface $input,
        private OutputInterface $output
    ) {
        if ($output instanceof ConsoleOutput) {
            $terminal = new Terminal();
            $this->lineLength = $terminal->getWidth();
        }
        $output->getFormatter()->setStyle(
            'eon',
            new OutputFormatterStyle('#ffffff', '#ff073a')
        );
        $output->getFormatter()->setStyle(
            'info',
            new OutputFormatterStyle('#ff073a')
        );
        parent::__construct($input, $output);
    }

    public function title(string $message)
    {
        $this->writeln([
            sprintf('<eon>%s</>', str_repeat(' ', $this->lineLength)),
            sprintf('<eon> %s </>', str_pad($message, $this->lineLength - 2, ' ')),
            sprintf('<eon>%s</>', str_repeat(' ', $this->lineLength)),
        ]);
    }

    public function createTable(): Table
    {
        $output = $this->output instanceof ConsoleOutputInterface ? $this->output->section() : $this->output;
        $style = clone Table::getStyleDefinition('borderless');
        $style->setCellHeaderFormat('<info>%s</info>');

        return (new Table($output))->setStyle($style);
    }
}
