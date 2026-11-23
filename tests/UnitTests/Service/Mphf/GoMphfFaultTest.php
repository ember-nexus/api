<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Mphf;

use App\Factory\MphfFactory;
use App\Service\Mphf\GoMphf;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * Runs against the real FrankenPHP extension and `mphf-builder`, so it is skipped where they don't exist. A file which
 * is truncated while it is mapped would kill the process with SIGBUS; the extension has to survive that.
 */
#[Small]
#[CoversNothing]
class GoMphfFaultTest extends TestCase
{
    private const string BUILDER = '/usr/local/bin/mphf-builder';
    private const string ID = '02IiNr0GOoKiPMN4UD1Ie1GR6kUqzPQpQbT7B';

    private string $directory = '';

    protected function setUp(): void
    {
        if (!function_exists(MphfFactory::EXTENSION_FUNCTION) || !is_executable(self::BUILDER)) {
            $this->markTestSkipped('The mphf extension or mphf-builder is not available.');
        }
        $this->directory = sys_get_temp_dir().'/go-mphf-fault-test-'.bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        if ('' === $this->directory) {
            return;
        }
        (new GoMphf())->unload();
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    private function buildFile(): string
    {
        file_put_contents($this->directory.'/ids.csv', self::ID.";Data\n02IiNr0GOoKiPMN4UD1Ie1GR6kUqzPQpQbT7C;User\n");
        $path = $this->directory.'/20260101-0000.mphf';
        exec(sprintf('%s build --input %s --output %s 2>&1', self::BUILDER, escapeshellarg($this->directory.'/ids.csv'), escapeshellarg($path)), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        return $path;
    }

    public function testBuiltFileIsReadOnlyAndLookupWorks(): void
    {
        $path = $this->buildFile();
        $mphf = new GoMphf();

        $this->assertSame(0o444, fileperms($path) & 0o777);
        $this->assertTrue($mphf->load($path, '20260101-0000.mphf', false), $mphf->lastError());
        $this->assertSame('Data', $mphf->lookup(self::ID));
    }

    public function testTruncatedMappingDoesNotKillTheProcess(): void
    {
        $path = $this->buildFile();
        $mphf = new GoMphf();
        $this->assertTrue($mphf->load($path, '20260101-0000.mphf', false), $mphf->lastError());
        $this->assertSame('Data', $mphf->lookup(self::ID));

        // somebody (tests run as owner, so this needs the permission first) truncates the file under the mapping
        chmod($path, 0o644);
        file_put_contents($path, '');

        $this->assertSame('', $mphf->lookup(self::ID), 'a lookup in a faulting table answers with nothing');
        $this->assertStringContainsString('can no longer be read', $mphf->lastError());
        // the broken table was dropped, so the synchronizer sees "nothing loaded" and loads a good file again
        $this->assertSame('', $mphf->version());
        $this->assertSame('', $mphf->lookup(self::ID));

        // the extension is usable again
        $this->assertTrue($mphf->load($this->buildAgain(), '20260101-0001.mphf', false), $mphf->lastError());
        $this->assertSame('Data', $mphf->lookup(self::ID));
    }

    private function buildAgain(): string
    {
        $path = $this->directory.'/20260101-0001.mphf';
        exec(sprintf('%s build --input %s --output %s 2>&1', self::BUILDER, escapeshellarg($this->directory.'/ids.csv'), escapeshellarg($path)), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        return $path;
    }
}
