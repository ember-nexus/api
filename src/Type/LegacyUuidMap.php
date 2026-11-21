<?php

declare(strict_types=1);

namespace App\Type;

use Safe\DateTimeImmutable;
use Throwable;

/**
 * Maps legacy UUIDv4 element ids of old backups to UUIDsv2a7.
 *
 * The random part of the new uuid is derived deterministically from the legacy id. The timestamp part is taken from the
 * element's 'created' property; if it is not available, one fixed fallback timestamp is used for the whole run, e.g. the
 * import time of the backup. The first mapping of an id wins, so every occurrence of a legacy id maps to the same uuid.
 */
class LegacyUuidMap
{
    /** @var array<string, string> lowercase legacy uuid => base62sort uuid */
    private array $mapping = [];

    public function __construct(
        private readonly int $fallbackTimestampMs,
    ) {
    }

    public static function createFromNow(): self
    {
        return new self(Uuid::nowMs());
    }

    /**
     * Returns non-legacy ids unchanged.
     */
    public function mapId(string $id, ?int $timestampMs = null): string
    {
        $id = trim($id);
        if (!Uuid::isLegacyUuidV4($id)) {
            return $id;
        }
        $key = strtolower($id);
        if (!array_key_exists($key, $this->mapping)) {
            $this->mapping[$key] = Uuid::fromLegacyUuidV4($key, $timestampMs ?? $this->fallbackTimestampMs)->toString();
        }

        return $this->mapping[$key];
    }

    /**
     * Replaces id, start and end of a raw element; the element's own id takes its timestamp from 'data.created'.
     *
     * @param array<string, mixed> $rawData
     *
     * @return array<string, mixed>
     */
    public function mapRawElement(array $rawData): array
    {
        if (array_key_exists('id', $rawData) && is_string($rawData['id'])) {
            $rawData['id'] = $this->mapId($rawData['id'], $this->getCreatedTimestampMs($rawData));
        }
        foreach (['start', 'end'] as $key) {
            if (array_key_exists($key, $rawData) && is_string($rawData[$key])) {
                $rawData[$key] = $this->mapId($rawData[$key]);
            }
        }

        return $rawData;
    }

    public function count(): int
    {
        return count($this->mapping);
    }

    /**
     * @return array<string, string>
     */
    public function getMapping(): array
    {
        $mapping = $this->mapping;
        ksort($mapping, SORT_STRING);

        return $mapping;
    }

    /**
     * Sorted CSV, semicolon delimited, with a trailing newline.
     */
    public function toCsv(): string
    {
        $csv = "UUIDv4;UUIDsv2a7\n";
        foreach ($this->getMapping() as $old => $new) {
            $csv .= $old.';'.$new."\n";
        }

        return $csv;
    }

    /**
     * @param array<string, mixed> $rawData
     */
    private function getCreatedTimestampMs(array $rawData): ?int
    {
        $data = $rawData['data'] ?? null;
        if (!is_array($data) || !array_key_exists('created', $data) || !is_string($data['created'])) {
            return null;
        }
        try {
            $created = new DateTimeImmutable($data['created']);
        } catch (Throwable) {
            return null;
        }
        $timestampMs = $created->getTimestamp() * 1000 + intdiv((int) $created->format('u'), 1000);
        if ($timestampMs < 0 || $timestampMs > Uuid::MAX_TIMESTAMP_MS) {
            return null;
        }

        return $timestampMs;
    }
}
