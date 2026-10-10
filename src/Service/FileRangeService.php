<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client416RangeNotSatisfiableExceptionFactory;
use App\Type\ByteRange;

/**
 * Parses the HTTP `Range` header (RFC 9110, Section 14.1.2). Only a single range is supported, multiple ranges
 * result in a 400 Bad Request. Valid but unsatisfiable ranges result in a 416 Range Not Satisfiable.
 */
class FileRangeService
{
    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client416RangeNotSatisfiableExceptionFactory $client416RangeNotSatisfiableExceptionFactory,
    ) {
    }

    /**
     * Evaluates the `If-Range` precondition (RFC 9110, Section 13.1.5) of a request which contains a `Range` header:
     * the range is only served if the client still holds the current representation, otherwise the full file has to
     * be answered with `200`. Only strong entity tags are compared, a weak tag never matches. HTTP dates never
     * match, as no `Last-Modified` header is served for files, so the client can not have got one from this API.
     */
    public function isRangeConditionSatisfied(?string $ifRangeHeader, ?string $currentEtag): bool
    {
        if (null === $ifRangeHeader) {
            return true;
        }
        $ifRangeHeader = trim($ifRangeHeader);
        if (!str_starts_with($ifRangeHeader, '"') || null === $currentEtag) {
            return false;
        }

        return trim($ifRangeHeader, '"') === $currentEtag;
    }

    public function parseRangeHeader(string $rangeHeader, int $totalContentLength): ByteRange
    {
        [$rawStart, $rawEnd] = $this->matchRangeHeader($rangeHeader);

        if ($totalContentLength <= 0) {
            throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail('Requested range can not be satisfied, as the resource is empty.', ['totalLength' => $totalContentLength], $totalContentLength);
        }

        if ('' === $rawStart) {
            [$start, $end] = $this->resolveSuffixRange($rawEnd, $totalContentLength);
        } else {
            [$start, $end] = $this->resolveStartEndRange($rawStart, $rawEnd, $totalContentLength);
        }

        if ($end < $start) {
            throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail('Requested range must span at least 1 byte.', ['requestedStart' => $start, 'requestedEnd' => $end, 'totalLength' => $totalContentLength], $totalContentLength);
        }

        return new ByteRange($start, $end, $totalContentLength);
    }

    /**
     * @return array{0: string, 1: string} raw start and end as given in the header, either may be empty
     */
    private function matchRangeHeader(string $rangeHeader): array
    {
        if (1 !== \Safe\preg_match('/^bytes=(\d*)-(\d*)$/', trim($rangeHeader), $matches)) {
            // syntactically invalid, or multiple ranges requested
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not parse 'Range' header: '%s'. Only a single range of the form 'bytes=<start>-<end>', 'bytes=<start>-' or 'bytes=-<suffixLength>' is supported.", $rangeHeader));
        }

        $rawStart = $matches[1] ?? '';
        $rawEnd = $matches[2] ?? '';

        if ('' === $rawStart && '' === $rawEnd) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("Could not parse 'Range' header: 'bytes=-' does not specify a start or a suffix length.");
        }

        return [$rawStart, $rawEnd];
    }

    /**
     * @return array{0: int, 1: int} start and end byte offset, inclusive
     */
    private function resolveSuffixRange(string $rawSuffixLength, int $totalContentLength): array
    {
        // suffix range: last <suffixLength> bytes
        $suffixLength = (int) $rawSuffixLength;
        if ($suffixLength <= 0) {
            throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail('Requested suffix range must request at least 1 byte.', ['totalLength' => $totalContentLength], $totalContentLength);
        }

        return [max(0, $totalContentLength - $suffixLength), $totalContentLength - 1];
    }

    /**
     * @return array{0: int, 1: int} start and end byte offset, inclusive
     */
    private function resolveStartEndRange(string $rawStart, string $rawEnd, int $totalContentLength): array
    {
        $start = (int) $rawStart;
        if ($start >= $totalContentLength) {
            throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail(sprintf('Requested range start (%d) is beyond the end of the resource (%d bytes long).', $start, $totalContentLength), ['requestedStart' => $start, 'totalLength' => $totalContentLength], $totalContentLength);
        }

        if ('' === $rawEnd) {
            // open range: from <start> to the end of the resource
            return [$start, $totalContentLength - 1];
        }

        // clamped instead of rejected, see RFC 9110
        return [$start, min((int) $rawEnd, $totalContentLength - 1)];
    }
}
