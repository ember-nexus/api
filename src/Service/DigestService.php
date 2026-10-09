<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Type\DigestAlgorithm;
use Throwable;

/**
 * Formats and parses RFC 9530 `Repr-Digest` / `Content-Digest` header values, which are RFC 8941 Dictionaries of
 * `algorithm=:base64:` members. Other algorithm members are parsed but ignored; only `sha-256` is validated and
 * returned.
 */
class DigestService
{
    private const int SHA256_BYTE_LENGTH = 32;

    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function formatDigestHeaderValue(string $hexHash): string
    {
        return sprintf('%s=:%s:', DigestAlgorithm::SHA_256->value, base64_encode(\Safe\hex2bin($hexHash)));
    }

    /**
     * Returns the hex-encoded sha-256 hash declared in the header, or null if no supported algorithm is declared
     * (other, unsupported algorithms may still be present and are ignored). Throws if the header can not be parsed
     * as an RFC 8941 Dictionary of `algorithm=:base64:` members, if an algorithm is declared more than once, or if
     * the declared sha-256 value is not exactly 32 bytes long.
     */
    public function parseSha256HexFromHeaderValue(string $headerValue): ?string
    {
        $rawValuesByAlgorithm = $this->parseRawValuesByAlgorithm($headerValue);

        $algorithm = DigestAlgorithm::SHA_256->value;
        if (!array_key_exists($algorithm, $rawValuesByAlgorithm)) {
            return null;
        }

        $rawValue = $rawValuesByAlgorithm[$algorithm];
        if (self::SHA256_BYTE_LENGTH !== strlen($rawValue)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not parse digest header '%s': algorithm '%s' must be exactly %d bytes long, got %d.", $headerValue, $algorithm, self::SHA256_BYTE_LENGTH, strlen($rawValue)));
        }

        return bin2hex($rawValue);
    }

    /**
     * Parses an RFC 8941 Dictionary of `algorithm=:base64:` members (no parameters are supported, as none are
     * defined by RFC 9530) into their decoded byte values, keyed by algorithm. Unsupported algorithms are included
     * as well; only {@see DigestAlgorithm::SHA_256} is validated and used by callers of this class.
     *
     * @return array<string, string> raw byte values by algorithm name
     */
    private function parseRawValuesByAlgorithm(string $headerValue): array
    {
        $rawValuesByAlgorithm = [];

        foreach (explode(',', $headerValue) as $member) {
            if (1 !== \Safe\preg_match('/^\s*([a-z*][a-z0-9_.*-]*)=:([^:]*):\s*$/', $member, $matches)) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not parse digest header '%s': malformed member '%s'.", $headerValue, trim($member)));
            }

            $algorithm = $matches[1] ?? '';
            if (array_key_exists($algorithm, $rawValuesByAlgorithm)) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not parse digest header '%s': algorithm '%s' is declared more than once.", $headerValue, $algorithm));
            }

            try {
                $rawValuesByAlgorithm[$algorithm] = \Safe\base64_decode($matches[2] ?? '', true);
            } catch (Throwable) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not parse digest header '%s': algorithm '%s' does not have a validly base64-encoded value.", $headerValue, $algorithm));
            }
        }

        return $rawValuesByAlgorithm;
    }
}
