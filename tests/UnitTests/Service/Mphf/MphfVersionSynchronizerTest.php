<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Mphf;

use App\Contract\MphfInterface;
use App\Factory\Type\RedisKeyFactory;
use App\Service\Mphf\MphfStorageService;
use App\Service\Mphf\MphfVersionSynchronizer;
use App\Service\Mphf\SystemBucketService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use RuntimeException;

#[Small]
#[CoversClass(MphfVersionSynchronizer::class)]
class MphfVersionSynchronizerTest extends TestCase
{
    use ProphecyTrait;

    private const string VERSION = '20260101-0317.mphf';

    private string $directory;
    private ObjectProphecy $redis;
    private ObjectProphecy $mphf;
    private ObjectProphecy $bucket;
    private MphfStorageService $storage;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/mphf-sync-test-'.bin2hex(random_bytes(6));
        $this->storage = new MphfStorageService('memory', $this->directory);
        $this->redis = $this->prophesize(Client::class);
        $this->mphf = $this->prophesize(MphfInterface::class);
        $this->mphf->isAvailable()->willReturn(true);
        $this->bucket = $this->prophesize(SystemBucketService::class);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    private function create(bool $enabled = true): MphfVersionSynchronizer
    {
        return new MphfVersionSynchronizer(
            $this->redis->reveal(),
            new RedisKeyFactory(),
            $this->mphf->reveal(),
            $this->storage,
            $this->bucket->reveal(),
            new NullLogger(),
            $enabled,
            true,
            1,
        );
    }

    private function putLocalFile(): void
    {
        $this->storage->ensureDirectory();
        file_put_contents($this->directory.'/'.self::VERSION, MphfFixture::file('x'));
    }

    public function testDoesNothingWhenDisabledOrUnavailable(): void
    {
        $this->redis->get(Argument::any())->shouldNotBeCalled();
        $this->create(false)->synchronize();

        $this->mphf->isAvailable()->willReturn(false);
        $this->create()->synchronize();
    }

    public function testNoRedisVersionUnloadsLoadedTable(): void
    {
        $this->redis->get('mphf-version')->willReturn(null);
        $this->mphf->version()->willReturn('old');
        $this->mphf->unload()->shouldBeCalledOnce();

        $synchronizer = $this->create();
        $synchronizer->synchronize();

        $this->assertSame('', $synchronizer->getCurrentVersion());
    }

    public function testRedisFailureKeepsState(): void
    {
        $this->redis->get('mphf-version')->willThrow(new RuntimeException('down'));
        $this->mphf->unload()->shouldNotBeCalled();

        $synchronizer = $this->create();
        $synchronizer->synchronize();

        $this->assertNull($synchronizer->getCurrentVersion());
    }

    public function testSameVersionIsNotLoadedAgain(): void
    {
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn(self::VERSION);
        $this->mphf->load(Argument::cetera())->shouldNotBeCalled();

        $synchronizer = $this->create();
        $synchronizer->synchronize();

        $this->assertTrue($synchronizer->isLoadedVersionCurrent());
    }

    public function testInvalidVersionIsIgnored(): void
    {
        $this->redis->get('mphf-version')->willReturn('../evil');
        $this->mphf->version()->willReturn('');
        $this->mphf->load(Argument::cetera())->shouldNotBeCalled();

        $synchronizer = $this->create();
        $synchronizer->synchronize();

        $this->assertFalse($synchronizer->hasPendingDownload());
        $this->assertFalse($synchronizer->isLoadedVersionCurrent());
    }

    public function testLoadsLocalFile(): void
    {
        $this->putLocalFile();
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn('');
        $this->mphf->load($this->directory.'/'.self::VERSION, self::VERSION, true)->shouldBeCalledOnce()->willReturn(true);

        $this->create()->ensureSynchronized();
    }

    public function testFailedLoadIsLoggedOnly(): void
    {
        $this->putLocalFile();
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn('');
        $this->mphf->load(Argument::cetera())->willReturn(false);
        $this->mphf->lastError()->willReturn('bad');

        $this->create()->synchronize();

        $this->assertFileExists($this->directory.'/'.self::VERSION);
    }

    public function testDamagedLocalFileIsRemovedAndFetchedAgain(): void
    {
        $this->storage->ensureDirectory();
        // truncated after it was published: the preface promises more content than the file has
        file_put_contents($this->directory.'/'.self::VERSION, substr(MphfFixture::file('payload'), 0, -3));
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn('');
        $this->mphf->load(Argument::cetera())->willReturn(false);
        $this->mphf->lastError()->willReturn('truncated');

        $synchronizer = $this->create();
        $synchronizer->synchronize();

        $this->assertFileDoesNotExist($this->directory.'/'.self::VERSION);
        $this->assertTrue($synchronizer->hasPendingDownload());
    }

    public function testMissingFileIsRememberedAndDownloadedLater(): void
    {
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn('');
        $this->bucket->download('mphf/'.self::VERSION, $this->directory.'/'.self::VERSION.'.tmp')->will(function (array $args): void {
            file_put_contents($args[1], MphfFixture::file('x'));
        })->shouldBeCalledOnce();
        $this->mphf->load($this->directory.'/'.self::VERSION, self::VERSION, true)->shouldBeCalledOnce()->willReturn(true);

        $synchronizer = $this->create();
        $synchronizer->synchronize();
        $this->assertTrue($synchronizer->hasPendingDownload());

        $synchronizer->downloadPending();

        $this->assertFalse($synchronizer->hasPendingDownload());
        $this->assertFileExists($this->directory.'/'.self::VERSION);
    }

    public function testDownloadNowInCli(): void
    {
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn('');
        $this->bucket->download(Argument::cetera())->will(function (array $args): void {
            file_put_contents($args[1], MphfFixture::file('x'));
        });
        $this->mphf->load(Argument::cetera())->shouldBeCalledOnce()->willReturn(true);

        $this->create()->synchronize(true);
    }

    public function testCorruptDownloadIsDiscarded(): void
    {
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn('');
        $this->bucket->download(Argument::cetera())->will(function (array $args): void {
            file_put_contents($args[1], str_repeat('z', 50));
        });
        $this->mphf->load(Argument::cetera())->shouldNotBeCalled();

        $this->create()->synchronize(true);

        $this->assertFileDoesNotExist($this->directory.'/'.self::VERSION.'.tmp');
        $this->assertFileDoesNotExist($this->directory.'/'.self::VERSION);
    }

    public function testDownloadPendingWithoutPendingIsNoop(): void
    {
        $this->bucket->download(Argument::cetera())->shouldNotBeCalled();

        $this->create()->downloadPending();
    }

    public function testDownloadSkippedWhileAnotherProcessHoldsTheLock(): void
    {
        $this->redis->get('mphf-version')->willReturn(self::VERSION);
        $this->mphf->version()->willReturn('');
        $this->bucket->download(Argument::cetera())->shouldNotBeCalled();
        $synchronizer = $this->create();
        $synchronizer->synchronize();
        $this->storage->ensureDirectory();
        $handle = fopen($this->storage->getLockFilePath(), 'c');
        $this->assertNotFalse($handle);
        flock($handle, LOCK_EX);

        try {
            $synchronizer->downloadPending(false);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function testSynchronizeIfStaleAndReset(): void
    {
        $this->redis->get('mphf-version')->shouldBeCalledTimes(3)->willReturn(null);
        $this->mphf->version()->willReturn('');
        $synchronizer = $this->create();

        $synchronizer->synchronizeIfStale();
        $synchronizer->synchronizeIfStale();
        $synchronizer->reset();
        $this->assertNull($synchronizer->getCurrentVersion());
        $synchronizer->ensureSynchronized();
        $synchronizer->ensureSynchronized();
        $synchronizer->reset();
        $synchronizer->ensureSynchronized();
    }
}
