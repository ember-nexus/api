<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\EtagCalculator;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Safe\DateTime;

#[Small]
#[CoversClass(EtagCalculator::class)]
class EtagCalculatorTest extends TestCase
{
    use ProphecyTrait;

    public function testEmptyEtagStillReturnsEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etag = $etagCalculator->getEtag();
        $this->assertSame('9Ie0RnaeBuE', (string) $etag);
    }

    public function testDifferentSeedsResultInDifferentEtags(): void
    {
        $etagCalculator1 = new EtagCalculator('seed 1');
        $etag1 = $etagCalculator1->getEtag();
        $etagCalculator2 = new EtagCalculator('seed 2');
        $etag2 = $etagCalculator2->getEtag();
        $this->assertSame('UuHklHPLe7U', (string) $etag1);
        $this->assertSame('5dKP7ScG1I9', (string) $etag2);
        $this->assertNotSame((string) $etag1, (string) $etag2);
    }

    public function testAddDateTimeChangesEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->addDateTime(DateTime::createFromFormat('Y-m-d H:i:s', '2024-01-17 00:00:00'));
        $etag = $etagCalculator->getEtag();

        $emptyEtagCalculator = new EtagCalculator('seed');
        $emptyEtag = $emptyEtagCalculator->getEtag();

        $this->assertNotSame((string) $emptyEtag, (string) $etag);
        $this->assertSame('dFOo1A9s0Pd', (string) $etag);
    }

    public function testAddUuidChangesEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->addUuid(Uuid::fromString('02IiNr0G8viOVKk9hALkhZIQdPfQB1JnQyjPN'));
        $etag = $etagCalculator->getEtag();

        $emptyEtagCalculator = new EtagCalculator('seed');
        $emptyEtag = $emptyEtagCalculator->getEtag();

        $this->assertNotSame((string) $emptyEtag, (string) $etag);
        $this->assertSame('LMLs4i3Z3KC', (string) $etag);
    }

    public function testAddStringChangesEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->addString('some string');
        $etag = $etagCalculator->getEtag();

        $emptyEtagCalculator = new EtagCalculator('seed');
        $emptyEtag = $emptyEtagCalculator->getEtag();

        $this->assertNotSame((string) $emptyEtag, (string) $etag);
        $this->assertSame('WjVTubjtlPd', (string) $etag);
    }

    public function testCallingGetEtagMultipleTimesReturnsTheSameEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $this->assertSame('9Ie0RnaeBuE', (string) $etagCalculator->getEtag());
        $this->assertSame('9Ie0RnaeBuE', (string) $etagCalculator->getEtag());
        $this->assertSame('9Ie0RnaeBuE', (string) $etagCalculator->getEtag());
    }

    public function testAddDateTimeAfterGettingEtagResultsInException(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->getEtag();

        $this->expectExceptionMessage('Etag is already finalized, no new data can be added.');
        $etagCalculator->addDateTime(DateTime::createFromFormat('Y-m-d H:i:s', '2024-01-17 00:00:00'));
    }

    public function testAddUuidAfterGettingEtagResultsInException(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->getEtag();

        $this->expectExceptionMessage('Etag is already finalized, no new data can be added.');
        $etagCalculator->addUuid(Uuid::fromString('02IiNr0G8viOVKk9hALkhZIQdPfQB1JnQyjPN'));
    }

    public function testAddStringAfterGettingEtagResultsInException(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->getEtag();

        $this->expectExceptionMessage('Etag is already finalized, no new data can be added.');
        $etagCalculator->addString('some string');
    }
}
