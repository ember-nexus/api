<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationCommand\System;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class SystemHealthcheckTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testSystemHealthcheckHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console healthcheck --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/system-healthcheck-help.html', $commandOutput);
    }

    public function testSystemHealthcheck(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console healthcheck --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/system-healthcheck.html',
            $commandOutput,
            [
                'Neo4j version:',
                'MongoDB version:',
                'Elasticsearch version:',
                'Redis version:',
                'RabbitMQ version:',
                'Alpine version:',
                'NGINX Unit version:',
                'PHP version:',
            ]
        );
    }
}
