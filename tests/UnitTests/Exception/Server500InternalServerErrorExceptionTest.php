<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Server500InternalServerErrorException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Server500InternalServerErrorException::class)]
class Server500InternalServerErrorExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $server500InternalServerErrorException = new Server500InternalServerErrorException(
            'type',
        );

        $this->assertSame('type', $server500InternalServerErrorException->getType());
        $this->assertSame('Internal server error', $server500InternalServerErrorException->getTitle());
        $this->assertSame(500, $server500InternalServerErrorException->getStatus());
        $this->assertSame('', $server500InternalServerErrorException->getDetail());
        $this->assertNull($server500InternalServerErrorException->getInstance());
        $this->assertSame('', $server500InternalServerErrorException->getMessage());
        $this->assertNull($server500InternalServerErrorException->getPrevious());
        $this->assertSame(0, $server500InternalServerErrorException->getCode());
    }

    public function testGetter(): void
    {
        $server500InternalServerErrorException = new Server500InternalServerErrorException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $server500InternalServerErrorException->getType());
        $this->assertSame('title', $server500InternalServerErrorException->getTitle());
        $this->assertSame(123, $server500InternalServerErrorException->getStatus());
        $this->assertSame('detail', $server500InternalServerErrorException->getDetail());
        $this->assertSame('instance', $server500InternalServerErrorException->getInstance());
        $this->assertSame('', $server500InternalServerErrorException->getMessage());
        $this->assertNull($server500InternalServerErrorException->getPrevious());
        $this->assertSame(0, $server500InternalServerErrorException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $server500InternalServerErrorException = new Server500InternalServerErrorException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $server500InternalServerErrorException->getMessage());
            $this->assertSame($exception, $server500InternalServerErrorException->getPrevious());
            $this->assertSame(0, $server500InternalServerErrorException->getCode());
        }
    }
}
