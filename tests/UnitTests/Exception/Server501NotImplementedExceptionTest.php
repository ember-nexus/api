<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Server501NotImplementedException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Server501NotImplementedException::class)]
class Server501NotImplementedExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $server501NotImplementedException = new Server501NotImplementedException(
            'type',
        );

        $this->assertSame('type', $server501NotImplementedException->getType());
        $this->assertSame('Not implemented', $server501NotImplementedException->getTitle());
        $this->assertSame(501, $server501NotImplementedException->getStatus());
        $this->assertSame('Endpoint is currently not implemented.', $server501NotImplementedException->getDetail());
        $this->assertNull($server501NotImplementedException->getInstance());
        $this->assertSame('', $server501NotImplementedException->getMessage());
        $this->assertNull($server501NotImplementedException->getPrevious());
        $this->assertSame(0, $server501NotImplementedException->getCode());
    }

    public function testGetter(): void
    {
        $server501NotImplementedException = new Server501NotImplementedException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $server501NotImplementedException->getType());
        $this->assertSame('title', $server501NotImplementedException->getTitle());
        $this->assertSame(123, $server501NotImplementedException->getStatus());
        $this->assertSame('detail', $server501NotImplementedException->getDetail());
        $this->assertSame('instance', $server501NotImplementedException->getInstance());
        $this->assertSame('', $server501NotImplementedException->getMessage());
        $this->assertNull($server501NotImplementedException->getPrevious());
        $this->assertSame(0, $server501NotImplementedException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $server501NotImplementedException = new Server501NotImplementedException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $server501NotImplementedException->getMessage());
            $this->assertSame($exception, $server501NotImplementedException->getPrevious());
            $this->assertSame(0, $server501NotImplementedException->getCode());
        }
    }
}
