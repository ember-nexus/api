<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Exception\InvalidUuidException;
use App\Type\InvalidUuidReason;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uuid::class)]
class UuidTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function vectorProvider(): array
    {
        return [
            'P1 nil' => ['02070007-00000000-0000-7000-F000-000000000000-00000000000000', '02IiNr00000000E4f3g8e03szAfOwhDmRTjRw'],
            'P2 max' => ['02070007-FFFFFFFF-FFFF-7FFF-FFFF-FFFFFFFFFFFF-FFFFFFFFFFFFFF', '02IiNrfTLKuZnU1aLdBzpHBoc6H0npLqpYfvj'],
            'P3' => ['02070007-017F22E2-79B0-7FE6-F76E-2B86F151FB04-E6B4400B21E888', '02IiNr0F21ErqJMNbTf7FlE0ME3GTCIXfryHo'],
            'P4' => ['02070007-01A1FC4B-4C3D-7C10-F288-2CAAA0662FD8-CCF69A89204F6A', '02IiNr0GOnGDqcho5adnseWkNNcoz7fA7tFnO'],
            'P5' => ['02070007-017F22E2-79B1-7000-F000-000000000000-00000000000000', '02IiNr0F21ErqJqYK6dYhYBOAinKT5xp0ZEjQ'],
        ];
    }

    #[DataProvider('vectorProvider')]
    public function testVectors(string $hex, string $base62): void
    {
        $fromHex = Uuid::fromString($hex);
        $fromBase62 = Uuid::fromString($base62);

        $this->assertSame($base62, $fromHex->toString());
        $this->assertSame($hex, $fromBase62->toHex());
        $this->assertTrue($fromHex->equals($fromBase62));
        $this->assertSame($base62, (string) $fromHex);
        $this->assertSame($base62, $fromHex->jsonSerialize());
        $this->assertSame(strtolower(str_replace('-', '', $hex)), bin2hex($fromHex->toBytes()));
        $this->assertTrue($fromHex->equals(Uuid::fromString(strtolower(str_replace('-', '', $hex)))));
    }

    public function testTimestamp(): void
    {
        $this->assertSame(1645557742000, Uuid::fromString('02IiNr0F21ErqJMNbTf7FlE0ME3GTCIXfryHo')->getTimestampMs());
    }

    public function testOrdering(): void
    {
        $values = [
            '02IiNr00000000E4f3g8e03szAfOwhDmRTjRw',
            '02IiNr0F21ErqJMNbTf7FlE0ME3GTCIXfryHo',
            '02IiNr0F21ErqJqYK6dYhYBOAinKT5xp0ZEjQ',
            '02IiNr0GOnGDqcho5adnseWkNNcoz7fA7tFnO',
            '02IiNrfTLKuZnU1aLdBzpHBoc6H0npLqpYfvj',
        ];
        $sorted = $values;
        sort($sorted, SORT_STRING);
        $this->assertSame($values, $sorted);
    }

    /**
     * @return array<string, array{string, InvalidUuidReason}>
     */
    public static function invalidProvider(): array
    {
        return [
            'too short' => ['02IiNr0F21ErqJMNbTf7FlE0ME3GTCIXfryH', InvalidUuidReason::UNKNOWN_FORMAT],
            'invalid character' => ['02IiNr0F21ErqJMNbTf7FlE0ME3GTCIXfryH-', InvalidUuidReason::INVALID_CHARACTER],
            'overflow' => ['02IiNrfTLKuZnUHfdxlNasV4qfZEFyHdnyieG', InvalidUuidReason::OVERFLOW],
            'wrong block' => ['02iInr0F21ErqJMNbTf7FlE0ME3GTCIXfryHo', InvalidUuidReason::UNSUPPORTED_VARIANT],
            'wrong version' => ['02070007-017F22E2-79B0-6FE6-F76E-2B86F151FB04-E6B4400B21E888', InvalidUuidReason::INVALID_VERSION],
            'wrong variant' => ['02070007-017F22E2-79B0-7FE6-876E-2B86F151FB04-E6B4400B21E888', InvalidUuidReason::INVALID_VARIANT],
            'moved dash' => ['02070007-017F22E27-9B0-7FE6-F76E-2B86F151FB04-E6B4400B21E888', InvalidUuidReason::UNKNOWN_FORMAT],
            'empty' => ['', InvalidUuidReason::UNKNOWN_FORMAT],
            'whitespace' => ['   ', InvalidUuidReason::UNKNOWN_FORMAT],
            'legacy v4' => ['aee9e678-42fa-48cf-85e3-33638bebf0b1', InvalidUuidReason::LEGACY_UUID_V4],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalid(string $value, InvalidUuidReason $reason): void
    {
        try {
            Uuid::fromString($value);
            $this->fail('Expected exception.');
        } catch (InvalidUuidException $exception) {
            $this->assertSame($reason, $exception->getReason());
        }
        $this->assertFalse(Uuid::isValid($value));
    }

    public function testGenerate(): void
    {
        $uuid = Uuid::generate(1645557742000);
        $this->assertSame(37, strlen($uuid->toString()));
        $this->assertStringStartsWith('02IiNr', $uuid->toString());
        $this->assertSame(1645557742000, $uuid->getTimestampMs());
        $this->assertTrue(Uuid::fromString($uuid->toString())->equals($uuid));
        $this->assertTrue(Uuid::fromString($uuid->toHex())->equals($uuid));
        $this->assertFalse(Uuid::generate()->equals(Uuid::generate()));
        $this->assertEqualsWithDelta(time() * 1000, Uuid::generate()->getTimestampMs(), 5000);
    }

    public function testGenerateTimestampOutOfRange(): void
    {
        $this->expectException(InvalidUuidException::class);
        Uuid::generate(-1);
    }

    public function testRoundTripAndLength(): void
    {
        for ($i = 0; $i < 500; ++$i) {
            $uuid = Uuid::generate(random_int(0, Uuid::MAX_TIMESTAMP_MS));
            $string = $uuid->toString();
            $this->assertSame(37, strlen($string));
            $this->assertTrue(Uuid::fromString($string)->equals($uuid));
            $this->assertTrue(Uuid::fromBytes($uuid->toBytes())->equals($uuid));
        }
    }

    public function testLegacyUuidV4Mapping(): void
    {
        $legacy = 'aee9e678-42fa-48cf-85e3-33638bebf0b1';
        $a = Uuid::fromLegacyUuidV4($legacy, 1577836800000);
        $b = Uuid::fromLegacyUuidV4(strtoupper($legacy), 1577836800000);
        $this->assertTrue($a->equals($b));
        $this->assertSame(1577836800000, $a->getTimestampMs());

        // the 122 random bits of the uuid v4 are preserved as the leading random bits
        $randomA = $this->randomBits($a);
        $v4Bits = '';
        foreach (str_split(str_replace('-', '', $legacy), 2) as $byte) {
            $v4Bits .= str_pad(decbin((int) hexdec($byte)), 8, '0', STR_PAD_LEFT);
        }
        $v4Random = substr($v4Bits, 0, 48).substr($v4Bits, 52, 12).substr($v4Bits, 66);
        $this->assertSame($v4Random, substr($randomA, 0, 122));

        $this->assertFalse(Uuid::fromLegacyUuidV4($legacy, 1577836800001)->equals($a));
    }

    public function testLegacyUuidV4MappingRejectsInvalid(): void
    {
        $this->expectException(InvalidUuidException::class);
        Uuid::fromLegacyUuidV4('not-a-uuid', 0);
    }

    public function testRandomHex(): void
    {
        $uuid = Uuid::fromString('02070007-017F22E2-79B0-7FE6-F76E-2B86F151FB04-E6B4400B21E888');
        $this->assertSame(26, strlen($uuid->getRandomHex()));
        $this->assertStringEndsWith('e6b4400b21e888', $uuid->getRandomHex());
    }

    private function randomBits(Uuid $uuid): string
    {
        $bytes = $uuid->toBytes();
        $bits = '';
        $fields = [
            [ord($bytes[10]) & 0x0F, 4],
            [ord($bytes[11]), 8],
            [ord($bytes[12]) & 0x0F, 4],
        ];
        foreach ($fields as [$value, $width]) {
            $bits .= str_pad(decbin($value), $width, '0', STR_PAD_LEFT);
        }
        foreach (str_split(substr($bytes, 13), 1) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return $bits;
    }

    public function testStringIsCalculatedOnceAndDoesNotAffectComparison(): void
    {
        $uuid = Uuid::generate();
        $other = Uuid::fromString($uuid->toString());
        $untouched = Uuid::fromString($uuid->toString());

        $first = $uuid->toString();
        $this->assertSame($first, $uuid->toString());
        $this->assertSame($first, $other->toString());
        $this->assertSame($uuid->toHex(), Uuid::fromString($first)->toHex());
        // loose comparison must not depend on which instance already calculated its string
        $this->assertTrue($uuid == $untouched);
        $this->assertTrue(in_array($untouched, [$uuid]));
    }

    public function testParsedBase62StringsAreServedFromTheHotCacheWithTheirStringAlreadyKnown(): void
    {
        $string = '02IiNr0F21ErqJMNbTf7FlE0ME3GTCIXfryHo';

        $first = Uuid::fromString($string);
        $second = Uuid::fromString(' '.$string.' ');

        $this->assertSame($first, $second);
        $this->assertSame($string, $first->toString());
        $this->assertSame($string, Uuid::fromBytes($first->toBytes())->toString());
    }

    public function testHotCacheEvictsTheOldestEntryFirstAndKeepsTheMostRecentOnes(): void
    {
        $oldest = Uuid::generate();
        $oldestString = $oldest->toString();
        $parsedOldest = Uuid::fromString($oldestString);
        $this->assertSame($parsedOldest, Uuid::fromString($oldestString));

        $newest = null;
        for ($i = 0; $i < 8200; ++$i) {
            $newest = Uuid::fromString(Uuid::generate()->toString());
        }

        // the oldest entry was pushed out, so it is parsed again, but still correctly
        $reparsed = Uuid::fromString($oldestString);
        $this->assertNotSame($parsedOldest, $reparsed);
        $this->assertSame($oldestString, $reparsed->toString());
        $this->assertTrue($oldest->equals($reparsed));
        // the most recent entry is still cached
        $this->assertSame($newest, Uuid::fromString($newest->toString()));
    }
}
