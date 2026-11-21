<?php

declare(strict_types=1);

namespace App\Type;

use App\Exception\InvalidUuidException;
use JsonSerializable;
use Stringable;

/**
 * UUIDsv2a7 with 128 random bits (rand_a 12 + rand_b 60 + rand_c 56) as defined by draft-davis-uuidrev-uuid-long-00,
 * encoded as base62sort.
 *
 * Layout of the 27 raw bytes, big-endian:
 *
 *   bytes  0..3   encoding block 0x02070007
 *   bytes  4..9   unix_ts_ms (48 bit)
 *   byte  10..11  version 0x7 + rand_a (12 bit)
 *   byte  12..19  variant 0xF + rand_b (60 bit)
 *   byte  20..26  rand_c (56 bit)
 *
 * The base62sort form is the 6 character block followed by the 31 character body, both zero padded. As all values have
 * the same length, the byte-wise order of the strings equals the time order of the uuids.
 */
final class Uuid implements JsonSerializable, Stringable
{
    public const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    public const string BLOCK = '02IiNr';
    public const string BLOCK_BYTES = "\x02\x07\x00\x07";
    public const int BYTE_LENGTH = 27;
    public const int BASE62_LENGTH = 37;
    public const int HEX_LENGTH = 60;
    public const int HEX_LENGTH_WITHOUT_DASHES = 54;
    public const int RANDOM_BYTE_LENGTH = 16;
    public const int BODY_BYTE_LENGTH = 23;
    public const int BODY_BASE62_LENGTH = 31;
    public const int MAX_TIMESTAMP_MS = 0xFFFFFFFFFFFF;
    public const string HEX_PATTERN = '/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}-[0-9A-Fa-f]{14}$/';
    public const string LEGACY_V4_PATTERN = '/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-4[0-9A-Fa-f]{3}-[89ABab][0-9A-Fa-f]{3}-[0-9A-Fa-f]{12}$/';

    private function __construct(
        private readonly string $bytes,
    ) {
    }

    /**
     * Generates a new uuid; $timestampMs defaults to the current time.
     */
    public static function generate(?int $timestampMs = null): self
    {
        return self::fromTimestampAndRandomBytes($timestampMs ?? self::nowMs(), random_bytes(16));
    }

    public static function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000.0);
    }

    /**
     * Parses base62sort or base16 (with or without dashes). Legacy UUIDv4 strings are rejected.
     *
     * @throws InvalidUuidException
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);
        $length = strlen($value);
        if (self::BASE62_LENGTH === $length) {
            return self::fromBase62Sort($value);
        }
        if (self::HEX_LENGTH === $length || self::HEX_LENGTH_WITHOUT_DASHES === $length) {
            return self::fromHex($value);
        }
        if (1 === \Safe\preg_match(self::LEGACY_V4_PATTERN, $value)) {
            throw new InvalidUuidException(InvalidUuidReason::LEGACY_UUID_V4, sprintf("'%s' is a legacy UUIDv4, which is no longer supported.", $value));
        }
        throw new InvalidUuidException(InvalidUuidReason::UNKNOWN_FORMAT, sprintf("'%s' is neither a base62sort (%d characters) nor a base16 UUIDsv2a7 (%d or %d characters). A UUIDsv2a7 consists of %d bytes in total, of which the random part is %d bytes (128 bits) long.", $value, self::BASE62_LENGTH, self::HEX_LENGTH, self::HEX_LENGTH_WITHOUT_DASHES, self::BYTE_LENGTH, self::RANDOM_BYTE_LENGTH));
    }

    public static function isValid(string $value): bool
    {
        try {
            self::fromString($value);

            return true;
        } catch (InvalidUuidException) {
            return false;
        }
    }

    /**
     * @throws InvalidUuidException
     */
    public static function fromBase62Sort(string $value): self
    {
        if (self::BASE62_LENGTH !== strlen($value)) {
            throw new InvalidUuidException(InvalidUuidReason::UNKNOWN_FORMAT, sprintf("'%s' does not have the expected length of %d.", $value, self::BASE62_LENGTH));
        }
        if (1 !== \Safe\preg_match('/^[0-9A-Za-z]+$/', $value)) {
            throw new InvalidUuidException(InvalidUuidReason::INVALID_CHARACTER, sprintf("'%s' contains characters outside of the base62 alphabet.", $value));
        }
        if (!str_starts_with($value, self::BLOCK)) {
            throw new InvalidUuidException(InvalidUuidReason::UNSUPPORTED_VARIANT, sprintf("'%s' does not start with the UUID long encoding block '%s'.", $value, self::BLOCK));
        }
        $body = self::decodeBase62(substr($value, strlen(self::BLOCK)), $value);

        return self::fromBytes(self::BLOCK_BYTES.$body);
    }

    /**
     * @throws InvalidUuidException
     */
    public static function fromHex(string $value): self
    {
        $length = strlen($value);
        if (self::HEX_LENGTH === $length) {
            if (1 !== \Safe\preg_match(self::HEX_PATTERN, $value)) {
                throw new InvalidUuidException(InvalidUuidReason::UNKNOWN_FORMAT, sprintf("'%s' does not match the base16 format 8-8-4-4-4-12-14.", $value));
            }
            $value = str_replace('-', '', $value);
        } elseif (self::HEX_LENGTH_WITHOUT_DASHES === $length) {
            if (!ctype_xdigit($value)) {
                throw new InvalidUuidException(InvalidUuidReason::INVALID_CHARACTER, sprintf("'%s' contains non-hexadecimal characters.", $value));
            }
        } else {
            throw new InvalidUuidException(InvalidUuidReason::UNKNOWN_FORMAT, sprintf("'%s' does not have a valid base16 length.", $value));
        }

        return self::fromBytes(\Safe\hex2bin($value));
    }

    /**
     * @throws InvalidUuidException
     */
    public static function fromBytes(string $bytes): self
    {
        if (self::BYTE_LENGTH !== strlen($bytes)) {
            throw new InvalidUuidException(InvalidUuidReason::UNKNOWN_FORMAT, sprintf('Expected %d bytes, got %d.', self::BYTE_LENGTH, strlen($bytes)));
        }
        if (self::BLOCK_BYTES !== substr($bytes, 0, 4)) {
            throw new InvalidUuidException(InvalidUuidReason::UNSUPPORTED_VARIANT, sprintf('Unsupported UUID long encoding block 0x%s, expected 0x%s.', bin2hex(substr($bytes, 0, 4)), bin2hex(self::BLOCK_BYTES)));
        }
        if (0x70 !== (ord($bytes[10]) & 0xF0)) {
            throw new InvalidUuidException(InvalidUuidReason::INVALID_VERSION, sprintf('Expected version 7, got %d.', ord($bytes[10]) >> 4));
        }
        if (0xF0 !== (ord($bytes[12]) & 0xF0)) {
            throw new InvalidUuidException(InvalidUuidReason::INVALID_VARIANT, sprintf('Expected variant 0xF, got 0x%X.', ord($bytes[12]) >> 4));
        }

        return new self($bytes);
    }

    /**
     * Creates a uuid from a timestamp and 128 random bits, which are split into rand_a, rand_b and rand_c.
     *
     * @throws InvalidUuidException
     */
    public static function fromTimestampAndRandomBytes(int $timestampMs, string $random): self
    {
        if ($timestampMs < 0 || $timestampMs > self::MAX_TIMESTAMP_MS) {
            throw new InvalidUuidException(InvalidUuidReason::TIMESTAMP_OUT_OF_RANGE, sprintf('Timestamp %d is outside of the 48 bit range.', $timestampMs));
        }
        if (16 !== strlen($random)) {
            throw new InvalidUuidException(InvalidUuidReason::INVALID_LENGTH, sprintf('Expected 16 random bytes, got %d.', strlen($random)));
        }
        $timestamp = substr(pack('J', $timestampMs), 2);
        $randomValues = array_map('ord', str_split($random));
        $bytes = self::BLOCK_BYTES
            .$timestamp
            .chr(0x70 | ($randomValues[0] >> 4))
            .chr((($randomValues[0] & 0x0F) << 4) | ($randomValues[1] >> 4))
            .chr(0xF0 | ($randomValues[1] & 0x0F))
            .substr($random, 2);

        return new self($bytes);
    }

    /**
     * Deterministically maps a legacy UUIDv4 to a UUIDsv2a7: the 122 random bits of the UUIDv4 are used as the first 122
     * random bits, the remaining 6 bits are derived from a SHA-256 hash of the UUIDv4, and the timestamp is passed in.
     *
     * @throws InvalidUuidException
     */
    public static function fromLegacyUuidV4(string $uuidV4, int $timestampMs): self
    {
        $uuidV4 = trim($uuidV4);
        if (1 !== \Safe\preg_match(self::LEGACY_V4_PATTERN, $uuidV4)) {
            throw new InvalidUuidException(InvalidUuidReason::UNKNOWN_FORMAT, sprintf("'%s' is not a valid UUIDv4.", $uuidV4));
        }
        $hex = strtolower(str_replace('-', '', $uuidV4));
        $bits = '';
        foreach (str_split($hex, 2) as $byteHex) {
            $bits .= str_pad(decbin((int) hexdec($byteHex)), 8, '0', STR_PAD_LEFT);
        }
        // drop version (bits 48..51) and variant (bits 64..65)
        $randomBits = substr($bits, 0, 48).substr($bits, 52, 12).substr($bits, 66);
        $extra = ord(hash('sha256', 'ember-nexus:legacy-uuid-v4:'.$hex, true)[0]) & 0x3F;
        $randomBits .= str_pad(decbin($extra), 6, '0', STR_PAD_LEFT);

        $random = '';
        foreach (str_split($randomBits, 8) as $chunk) {
            $random .= chr((int) bindec($chunk) & 0xFF);
        }

        return self::fromTimestampAndRandomBytes($timestampMs, $random);
    }

    public static function isLegacyUuidV4(string $value): bool
    {
        return 1 === \Safe\preg_match(self::LEGACY_V4_PATTERN, trim($value));
    }

    public function toString(): string
    {
        return self::BLOCK.self::encodeBase62(substr($this->bytes, 4));
    }

    public function toHex(): string
    {
        $hex = strtoupper(bin2hex($this->bytes));

        return implode('-', [
            substr($hex, 0, 8),
            substr($hex, 8, 8),
            substr($hex, 16, 4),
            substr($hex, 20, 4),
            substr($hex, 24, 4),
            substr($hex, 28, 12),
            substr($hex, 40, 14),
        ]);
    }

    public function toBytes(): string
    {
        return $this->bytes;
    }

    /**
     * Hex string of the 13 trailing random bytes (26 digits, lowercase). Used for nested folder structures, as the
     * leading part of the uuid is a timestamp and therefore a bad sharding key.
     */
    public function getRandomHex(): string
    {
        return bin2hex(substr($this->bytes, -13));
    }

    public function getTimestampMs(): int
    {
        return (int) hexdec(bin2hex(substr($this->bytes, 4, 6)));
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->toBytes();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    private static function encodeBase62(string $body): string
    {
        $digits = array_map('ord', str_split($body));
        $result = '';
        for ($i = 0; $i < self::BODY_BASE62_LENGTH; ++$i) {
            $remainder = 0;
            foreach ($digits as $index => $digit) {
                $current = $remainder * 256 + $digit;
                $digits[$index] = intdiv($current, 62);
                $remainder = $current % 62;
            }
            $result = self::ALPHABET[$remainder].$result;
        }

        return $result;
    }

    private static function decodeBase62(string $body, string $original): string
    {
        $bytes = array_fill(0, self::BODY_BYTE_LENGTH, 0);
        foreach (str_split($body) as $char) {
            $carry = (int) strpos(self::ALPHABET, $char);
            for ($i = self::BODY_BYTE_LENGTH - 1; $i >= 0; --$i) {
                $current = $bytes[$i] * 62 + $carry;
                $bytes[$i] = $current & 0xFF;
                $carry = $current >> 8;
            }
            if ($carry > 0) {
                throw new InvalidUuidException(InvalidUuidReason::OVERFLOW, sprintf("'%s' exceeds the maximum value of a UUIDsv2a7.", $original));
            }
        }

        return pack('C*', ...$bytes);
    }
}
