<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Server500LogicErrorException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Server500LogicErrorException::class)]
class Server500LogicErrorExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $server500LogicErrorException = new Server500LogicErrorException(
            'type',
        );

        $this->assertSame('type', $server500LogicErrorException->getType());
        $this->assertSame('Internal server error', $server500LogicErrorException->getTitle());
        $this->assertSame(500, $server500LogicErrorException->getStatus());
        $this->assertSame('', $server500LogicErrorException->getDetail());
        $this->assertNull($server500LogicErrorException->getInstance());
        $this->assertSame('', $server500LogicErrorException->getMessage());
        $this->assertNull($server500LogicErrorException->getPrevious());
        $this->assertSame(0, $server500LogicErrorException->getCode());
    }

    public function testGetter(): void
    {
        $server500LogicErrorException = new Server500LogicErrorException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $server500LogicErrorException->getType());
        $this->assertSame('title', $server500LogicErrorException->getTitle());
        $this->assertSame(123, $server500LogicErrorException->getStatus());
        $this->assertSame('detail', $server500LogicErrorException->getDetail());
        $this->assertSame('instance', $server500LogicErrorException->getInstance());
        $this->assertSame('', $server500LogicErrorException->getMessage());
        $this->assertNull($server500LogicErrorException->getPrevious());
        $this->assertSame(0, $server500LogicErrorException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $server500LogicErrorException = new Server500LogicErrorException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $server500LogicErrorException->getMessage());
            $this->assertSame($exception, $server500LogicErrorException->getPrevious());
            $this->assertSame(0, $server500LogicErrorException->getCode());
        }
    }
}
