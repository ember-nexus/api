<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Console;

use App\Console\EmberNexusOutputWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Small]
#[CoversClass(EmberNexusOutputWrapper::class)]
class EmberNexusOutputWrapperTest extends TestCase
{
    use ProphecyTrait;

    public function testWriteMinimal(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->write(Argument::is('Minimal string.'), Argument::is(false), Argument::is(0))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->write('Minimal string.');
    }

    public function testWriteAll(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->write(Argument::is('Minimal string.'), Argument::is(true), Argument::is(1))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->write('Minimal string.', true, 1);
    }

    public function testWriteLineMinimal(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->writeln(Argument::is('  Minimal string.'), Argument::is(0))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->writeln('Minimal string.');
    }

    public function testWriteLineArray(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->writeln(Argument::is(['a', 'b', 'c']), Argument::is(0))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->writeln(['a', 'b', 'c']);
    }

    public function testWriteLineAll(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->writeln(Argument::is('  Minimal string.'), Argument::is(1))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->writeln('Minimal string.', 1);
    }

    public function testSetVerbosity(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->setVerbosity(Argument::is(123))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->setVerbosity(123);
    }

    public function testGetVerbosity(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->getVerbosity()
            ->shouldBeCalledOnce()
            ->willReturn(321);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(321, $emberNexusOutputWrapper->getVerbosity());
    }

    public function testIsQuietTrue(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isQuiet()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(true, $emberNexusOutputWrapper->isQuiet());
    }

    public function testIsQuietFalse(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isQuiet()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(false, $emberNexusOutputWrapper->isQuiet());
    }

    public function testIsVerboseTrue(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isVerbose()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(true, $emberNexusOutputWrapper->isVerbose());
    }

    public function testIsVerboseFalse(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isVerbose()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(false, $emberNexusOutputWrapper->isVerbose());
    }

    public function testIsVeryVerboseTrue(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isVeryVerbose()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(true, $emberNexusOutputWrapper->isVeryVerbose());
    }

    public function testIsVeryVerboseFalse(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isVeryVerbose()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(false, $emberNexusOutputWrapper->isVeryVerbose());
    }

    public function testIsDebugTrue(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isDebug()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(true, $emberNexusOutputWrapper->isDebug());
    }

    public function testIsDebugFalse(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isDebug()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(false, $emberNexusOutputWrapper->isDebug());
    }

    public function testSetDecoratedTrue(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->setDecorated(Argument::is(true))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->setDecorated(true);
    }

    public function testSetDecoratedFalse(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->setDecorated(Argument::is(false))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->setDecorated(false);
    }

    public function testIsDecoratedTrue(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isDecorated()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(true, $emberNexusOutputWrapper->isDecorated());
    }

    public function testIsDecoratedFalse(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->isDecorated()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame(false, $emberNexusOutputWrapper->isDecorated());
    }

    public function testSetFormatterFalse(): void
    {
        $formatter = $this->prophesize(OutputFormatterInterface::class)->reveal();

        $output = $this->prophesize(OutputInterface::class);
        $output
            ->setFormatter(Argument::is($formatter))
            ->shouldBeCalledOnce();

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $emberNexusOutputWrapper->setFormatter($formatter);
    }

    public function testGetFormatterFalse(): void
    {
        $formatter = $this->prophesize(OutputFormatterInterface::class)->reveal();

        $output = $this->prophesize(OutputInterface::class);
        $output
            ->getFormatter()
            ->shouldBeCalledOnce()
            ->willReturn($formatter);

        $emberNexusOutputWrapper = new EmberNexusOutputWrapper($output->reveal());
        $this->assertSame($formatter, $emberNexusOutputWrapper->getFormatter());
    }
}
