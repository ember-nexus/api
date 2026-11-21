<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Mphf;

use App\Factory\MphfFactory;
use App\Service\Mphf\NullMphf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(NullMphf::class)]
#[CoversClass(MphfFactory::class)]
class NullMphfTest extends TestCase
{
    public function testNullMphfIsInert(): void
    {
        $mphf = new NullMphf();

        $this->assertFalse($mphf->isAvailable());
        $this->assertFalse($mphf->load('/nope', 'v'));
        $mphf->unload();
        $this->assertSame('', $mphf->version());
        $this->assertSame('', $mphf->lookup('id'));
        $this->assertNotSame('', $mphf->lastError());
        $this->assertSame([], $mphf->info());
    }

    public function testFactoryReturnsNullMphfWhenDisabled(): void
    {
        $this->assertInstanceOf(NullMphf::class, (new MphfFactory(false))->create());
    }

    public function testFactoryReturnsAnImplementationWhenEnabled(): void
    {
        $mphf = (new MphfFactory(true))->create();

        // the Go extension is only present in the container images, not necessarily where the tests run
        $this->assertSame(function_exists(MphfFactory::EXTENSION_FUNCTION), !$mphf instanceof NullMphf);
    }
}
