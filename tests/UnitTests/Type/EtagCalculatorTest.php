<?php

namespace App\tests\UnitTests\Type;

use App\Type\EtagCalculator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;

class EtagCalculatorTest extends TestCase
{
    use ProphecyTrait;

    public function testEmptyEtagStillReturnsEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etag = $etagCalculator->getEtag();
        $this->assertSame('9Ie0RnaeBuE', $etag);
    }

    public function testDifferentSeedsResultInDifferentEtags(): void
    {
        $etagCalculator1 = new EtagCalculator('seed 1');
        $etag1 = $etagCalculator1->getEtag();
        $etagCalculator2 = new EtagCalculator('seed 2');
        $etag2 = $etagCalculator2->getEtag();
        $this->assertSame('UuHklHPLe7U', $etag1);
        $this->assertSame('5dKP7ScG1I9', $etag2);
        $this->assertNotSame($etag1, $etag2);
    }

    public function testAddDateTimeChangesEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->addDateTime(DateTime::createFromFormat('Y-m-d H:i:s', '2024-01-17 00:00:00'));
        $etag = $etagCalculator->getEtag();

        $this->assertNotSame('9Ie0RnaeBuE', $etag); // this is the etag without datetime
        $this->assertSame('8qVAho7SmVW', $etag);
    }

    public function testAddUuidChangesEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->addUuid(Uuid::fromString('67b1689e-e6c7-4463-a48a-74236fd5f08a'));
        $etag = $etagCalculator->getEtag();

        $this->assertNotSame('9Ie0RnaeBuE', $etag); // this is the etag without datetime
        $this->assertSame('D7h1i75muKb', $etag);
    }

    public function testCallingGetEtagMultipleTimesReturnsTheSameEtag(): void
    {
        $etagCalculator = new EtagCalculator('seed');
        $this->assertSame('9Ie0RnaeBuE', $etagCalculator->getEtag());
        $this->assertSame('9Ie0RnaeBuE', $etagCalculator->getEtag());
        $this->assertSame('9Ie0RnaeBuE', $etagCalculator->getEtag());
    }

    public function testAddDateTimeAfterGettingEtagResultsInException(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->getEtag();

        $this->expectExceptionMessage('Etag is already finalized, no new data can be added.');
        $etagCalculator->addDateTime(DateTime::createFromFormat('Y-m-d H:i:s', '2024-01-17 00:00:00'));
    }

    public function testAddUuidAfterGettingEtagResultsInException(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $etagCalculator = new EtagCalculator('seed');
        $etagCalculator->getEtag();

        $this->expectExceptionMessage('Etag is already finalized, no new data can be added.');
        $etagCalculator->addUuid(Uuid::fromString('67b1689e-e6c7-4463-a48a-74236fd5f08a'));
    }
}
