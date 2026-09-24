<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Cache;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class CacheClearEtagTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testCacheClearEtagHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cache:clear:etag --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/cache-clear-etag-help.html', $commandOutput);
    }

    public function testCacheClearEtag(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cache:clear:etag --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/cache-clear-etag.html',
            $commandOutput,
            [
                'Expired',
                '  .',
            ]
        );
    }
}
