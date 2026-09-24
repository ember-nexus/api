<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Backup;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;
use Symfony\Component\Dotenv\Dotenv;

class BackupFetchTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string FALLBACK_REFERENCE_DATASET_VERSION = '0.0.32';

    public function testBackupFetchHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:fetch --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-fetch-help.html',
            $commandOutput,
            [
            ]
        );
    }

    public function testBackupFetch(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:fetch --ansi reference-dataset https://github.com/ember-nexus/reference-dataset/archive/refs/tags/%s.zip | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion(),
            $this->getReferenceDatasetVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-fetch.html',
            $commandOutput,
            [
                ' [',
            ]
        );
    }

    /**
     * Uses the reference dataset version which is also used by the other tests (see bin/test-feature-prepare), so
     * that this test stays up to date automatically. A real environment variable wins over the value in `.env`.
     */
    private function getReferenceDatasetVersion(): string
    {
        $version = getenv('REFERENCE_DATASET_VERSION');
        if (false !== $version && '' !== $version) {
            return $version;
        }
        $envFile = self::PATH_TO_ROOT.'.env';
        if (is_file($envFile)) {
            $values = (new Dotenv())->parse(\Safe\file_get_contents($envFile));
            if (is_string($values['REFERENCE_DATASET_VERSION'] ?? null) && '' !== $values['REFERENCE_DATASET_VERSION']) {
                return $values['REFERENCE_DATASET_VERSION'];
            }
        }

        return self::FALLBACK_REFERENCE_DATASET_VERSION;
    }
}
