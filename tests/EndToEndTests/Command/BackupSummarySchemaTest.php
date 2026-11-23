<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\Group;

/**
 * The summary.json written by backup:create has to follow the schema which is documented for it.
 */
#[Group('command')]
class BackupSummarySchemaTest extends BaseCronTestCase
{
    private const string SCHEMA = __DIR__.'/../../../docs/public/schemas/backup-summary.schema.json';

    private string $backupName = '';

    protected function tearDown(): void
    {
        if ('' !== $this->backupName) {
            $path = __DIR__.'/../../../var/backup/'.$this->backupName;
            if (is_dir($path)) {
                exec('rm -rf '.escapeshellarg($path));
            }
        }
        parent::tearDown();
    }

    public function testSummaryOfBackupCreateFollowsTheSchema(): void
    {
        $this->backupName = 'summary-schema-test-'.bin2hex(random_bytes(4));

        [$exitCode, $output] = $this->runConsoleCommand('backup:create --no-files '.$this->backupName);
        $this->assertSame(0, $exitCode, $output);

        $summary = json_decode(\Safe\file_get_contents(__DIR__.'/../../../var/backup/'.$this->backupName.'/summary.json'));
        $validator = new Validator();
        $validator->validate($summary, (object) ['$ref' => 'file://'.realpath(self::SCHEMA)]);

        $this->assertTrue($validator->isValid(), json_encode($validator->getErrors(), JSON_PRETTY_PRINT));
        $this->assertGreaterThan(0, $summary->nodeCount);
        $this->assertContains('User', $summary->graphStructure->nodeTypes);
    }

    public function testSchemaIsPrettyPrintedWithTwoSpaces(): void
    {
        $raw = \Safe\file_get_contents(self::SCHEMA);

        $this->assertSame(
            json_encode(json_decode($raw), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
            // PHP indents with four spaces, the schema uses two
            preg_replace_callback('/^( +)/m', static fn (array $match): string => str_repeat(' ', strlen($match[1]) * 2), $raw),
        );
    }
}
