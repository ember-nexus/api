<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client416RangeNotSatisfiableExceptionFactory;
use App\Type\ByteRange;

/**
 * Parses the HTTP `Range` request header (RFC 9110, Section 14.1.2) for the single-range case.
 *
 * Only a single byte range is supported, as multiple ranges within one request ("multipart/byteranges") are not
 * implemented. The following forms are supported:
 *
 * - `bytes=<start>-<end>`: an explicit, inclusive range.
 * - `bytes=<start>-`: from `<start>` to the end of the resource.
 * - `bytes=-<suffixLength>`: the last `<suffixLength>` bytes of the resource.
 *
 * A syntactically invalid or unsupported (e.g. multi-range) header is ignored, per RFC 9110, in which case the
 * full resource should be served with a 200 response instead of a 206 partial one.
 */
class FileRangeService
{
    public function __construct(
        private Client416RangeNotSatisfiableExceptionFactory $client416RangeNotSatisfiableExceptionFactory,
    ) {
    }

    public function parseRangeHeader(?string $rangeHeader, int $totalContentLength): ?ByteRange
    {
        if (null === $rangeHeader) {
            return null;
        }

        if (1 !== \Safe\preg_match('/^bytes=(\d*)-(\d*)$/', trim($rangeHeader), $matches)) {
            // syntactically invalid, or multiple ranges requested (unsupported) -> ignore, serve full content
            return null;
        }

        $rawStart = $matches[1] ?? '';
        $rawEnd = $matches[2] ?? '';

        if ('' === $rawStart && '' === $rawEnd) {
            // "bytes=-" is not a valid range -> ignore, serve full content
            return null;
        }

        if ($totalContentLength <= 0) {
            throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail('Requested range can not be satisfied, as the resource is empty.', ['total-length' => $totalContentLength]);
        }

        if ('' === $rawStart) {
            // suffix range: last <suffixLength> bytes
            $suffixLength = (int) $rawEnd;
            if ($suffixLength <= 0) {
                throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail('Requested suffix range must request at least 1 byte.', ['total-length' => $totalContentLength]);
            }

            $start = max(0, $totalContentLength - $suffixLength);
            $end = $totalContentLength - 1;
        } else {
            $start = (int) $rawStart;
            if ($start >= $totalContentLength) {
                throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail(sprintf('Requested range start (%d) is beyond the end of the resource (%d bytes long).', $start, $totalContentLength), ['requested-start' => $start, 'total-length' => $totalContentLength]);
            }

            if ('' === $rawEnd) {
                // open range: from <start> to the end of the resource ("infinity")
                $end = $totalContentLength - 1;
            } else {
                // end can not exceed the resource's actual length, it is clamped instead of rejected
                $end = min((int) $rawEnd, $totalContentLength - 1);
            }
        }

        if ($end < $start) {
            throw $this->client416RangeNotSatisfiableExceptionFactory->createFromDetail('Requested range must span at least 1 byte.', ['requested-start' => $start, 'requested-end' => $end, 'total-length' => $totalContentLength]);
        }

        return new ByteRange($start, $end, $totalContentLength);
    }
}
