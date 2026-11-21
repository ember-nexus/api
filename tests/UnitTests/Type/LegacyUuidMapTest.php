<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\LegacyUuidMap;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(LegacyUuidMap::class)]
class LegacyUuidMapTest extends TestCase
{
    private const string LEGACY_A = 'aee9e678-42fa-48cf-85e3-33638bebf0b1';
    private const string LEGACY_B = '81cc2ca9-bf66-4b37-a082-2490d1211e18';
    private const int FALLBACK_MS = 1767225600000;

    public function testNonLegacyIdsAreKept(): void
    {
        $map = new LegacyUuidMap(self::FALLBACK_MS);
        $id = Uuid::generate()->toString();

        $this->assertSame($id, $map->mapId($id));
        $this->assertSame(0, $map->count());
    }

    public function testLegacyIdUsesFallbackTimestampAndIsStable(): void
    {
        $map = new LegacyUuidMap(self::FALLBACK_MS);

        $mapped = $map->mapId(self::LEGACY_A);

        $this->assertSame(self::FALLBACK_MS, Uuid::fromString($mapped)->getTimestampMs());
        $this->assertSame($mapped, $map->mapId(strtoupper(self::LEGACY_A)));
        // the first mapping wins, even if a later occurrence knows a better timestamp
        $this->assertSame($mapped, $map->mapId(self::LEGACY_A, 1000));
        $this->assertSame(1, $map->count());
    }

    public function testMappingIsDeterministicAcrossInstances(): void
    {
        $this->assertSame(
            (new LegacyUuidMap(self::FALLBACK_MS))->mapId(self::LEGACY_A),
            (new LegacyUuidMap(self::FALLBACK_MS))->mapId(self::LEGACY_A)
        );
    }

    public function testRawElementTakesTimestampFromCreatedProperty(): void
    {
        $map = new LegacyUuidMap(self::FALLBACK_MS);

        $mapped = $map->mapRawElement([
            'type' => 'Token',
            'id' => self::LEGACY_A,
            'data' => ['created' => '2020-01-01T00:00:00.123+00:00'],
        ]);

        $this->assertIsString($mapped['id']);
        $this->assertSame(1577836800123, Uuid::fromString($mapped['id'])->getTimestampMs());
    }

    public function testRawElementWithInvalidCreatedUsesFallback(): void
    {
        $map = new LegacyUuidMap(self::FALLBACK_MS);

        $mapped = $map->mapRawElement(['id' => self::LEGACY_A, 'data' => ['created' => 'not a date']]);

        $this->assertIsString($mapped['id']);
        $this->assertSame(self::FALLBACK_MS, Uuid::fromString($mapped['id'])->getTimestampMs());
    }

    public function testRelationReferencesUseMappingOfNodes(): void
    {
        $map = new LegacyUuidMap(self::FALLBACK_MS);
        $node = $map->mapRawElement(['id' => self::LEGACY_A, 'data' => ['created' => '2020-01-01T00:00:00+00:00']]);

        $relation = $map->mapRawElement(['id' => self::LEGACY_B, 'start' => self::LEGACY_A, 'end' => self::LEGACY_A, 'type' => 'X']);

        $this->assertSame($node['id'], $relation['start']);
        $this->assertSame($node['id'], $relation['end']);
        $this->assertSame(2, $map->count());
    }

    public function testCsvIsSortedAndEndsWithNewline(): void
    {
        $map = new LegacyUuidMap(self::FALLBACK_MS);
        $map->mapId(self::LEGACY_A);
        $map->mapId(self::LEGACY_B);

        $lines = explode("\n", $map->toCsv());

        $this->assertSame('UUIDv4;UUIDsv2a7', $lines[0]);
        $this->assertStringStartsWith(self::LEGACY_B.';', $lines[1]);
        $this->assertStringStartsWith(self::LEGACY_A.';', $lines[2]);
        $this->assertSame('', $lines[3]);
        $this->assertCount(4, $lines);
    }
}
