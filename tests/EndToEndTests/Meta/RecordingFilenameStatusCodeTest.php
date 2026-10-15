<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Meta;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\preg_match;
use function Safe\realpath;
use function Safe\scandir;

/**
 * Every recorded error response body under tests/recordings/ that is named after an HTTP status
 * code >= 400 (e.g. `404-response-body.json`) must actually carry that status somewhere in its
 * content. This catches recordings that were renamed, copy-pasted, or hand-authored without
 * re-capturing the real response - e.g. a `429-response-body.json` that still holds a stale `401`
 * Problem+JSON body.
 *
 * Filenames below 400 are skipped: a "200"/"206" etc. there documents the real status the server
 * answered the recorded request with, not a code the body is expected to talk about - e.g.
 * `GET /error/404/not-found` legitimately answers `200 OK` with a page that explains error 404,
 * and a successful file download's body is just the raw file bytes. There is nothing to check the
 * filename against in either case.
 *
 * This is not an end-to-end test in the usual sense (it makes no HTTP request, it inspects files
 * already produced by the example generation / end-to-end tests), but it lives alongside them
 * because it verifies their output and is meant to run in the same suite.
 *
 * `*-response-header.txt` recordings are deliberately NOT covered here: by design (see
 * BaseRequestTestCase::getHeadersFromRequest()) they only ever capture header key/value pairs,
 * never a status line, so there is nothing in their content that could confirm or contradict the
 * filename's status code.
 */
class RecordingFilenameStatusCodeTest extends TestCase
{
    private const string RECORDINGS_ROOT = __DIR__.'/../../recordings';

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideResponseBodyFiles(): iterable
    {
        $files = [];
        self::collectResponseBodyFiles(realpath(self::RECORDINGS_ROOT), $files);
        sort($files);
        foreach ($files as $file) {
            yield $file => [$file];
        }
    }

    /**
     * @param string[] $files
     */
    private static function collectResponseBodyFiles(string $dir, array &$files): void
    {
        foreach (scandir($dir) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir.'/'.$entry;
            if (is_dir($path)) {
                self::collectResponseBodyFiles($path, $files);
                continue;
            }
            if (1 === preg_match('/^\d{3}-.*response-body\.(json|txt)$/', $entry)) {
                $files[] = $path;
            }
        }
    }

    #[DataProvider('provideResponseBodyFiles')]
    public function testResponseBodyContainsItsFilenameStatusCode(string $path): void
    {
        $filename = basename($path);
        preg_match('/^(\d{3})-/', $filename, $matches);
        $statusCodeFromFilename = $matches[1] ?? '';

        if ((int) $statusCodeFromFilename < 400) {
            // Below 400 a "200" (or "206" etc.) in the filename documents the real status the
            // server answered the recorded request with, not a code the body is expected to talk
            // about - e.g. GET /error/404/not-found legitimately answers 200 OK with a page that
            // explains error 404, and a successful file download's body is just the raw file
            // bytes. Nothing to check the filename against here.
            $this->markTestSkipped('Status below 400: filename documents the real response status, not expected body content.');
        }

        $content = file_get_contents($path);

        if (str_ends_with($filename, '.json')) {
            $decoded = json_decode($content, true);
            if (!is_array($decoded) || !array_key_exists('status', $decoded)) {
                $this->markTestSkipped('Not a Problem+JSON body: no "status" field to compare the filename against.');
            }
            $this->assertSame(
                (int) $statusCodeFromFilename,
                (int) $decoded['status'],
                sprintf(
                    '%s is named after status %s, but its "status" field says %s.',
                    $path,
                    $statusCodeFromFilename,
                    $decoded['status']
                )
            );

            return;
        }

        $this->assertStringContainsString(
            $statusCodeFromFilename,
            $content,
            sprintf(
                '%s is named after status %s, but that number does not appear anywhere in its content.',
                $path,
                $statusCodeFromFilename
            )
        );
    }
}
