<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Server503ServiceUnavailableException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Server503ServiceUnavailableException::class)]
class Server503ServiceUnavailableExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $server503ServiceUnavailableException = new Server503ServiceUnavailableException(
            'type',
        );

        $this->assertSame('type', $server503ServiceUnavailableException->getType());
        $this->assertSame('Service unavailable', $server503ServiceUnavailableException->getTitle());
        $this->assertSame(503, $server503ServiceUnavailableException->getStatus());
        $this->assertSame('', $server503ServiceUnavailableException->getDetail());
        $this->assertNull($server503ServiceUnavailableException->getInstance());
        $this->assertSame('', $server503ServiceUnavailableException->getMessage());
        $this->assertNull($server503ServiceUnavailableException->getPrevious());
        $this->assertSame(0, $server503ServiceUnavailableException->getCode());
    }

    public function testGetter(): void
    {
        $server503ServiceUnavailableException = new Server503ServiceUnavailableException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $server503ServiceUnavailableException->getType());
        $this->assertSame('title', $server503ServiceUnavailableException->getTitle());
        $this->assertSame(123, $server503ServiceUnavailableException->getStatus());
        $this->assertSame('detail', $server503ServiceUnavailableException->getDetail());
        $this->assertSame('instance', $server503ServiceUnavailableException->getInstance());
        $this->assertSame('', $server503ServiceUnavailableException->getMessage());
        $this->assertNull($server503ServiceUnavailableException->getPrevious());
        $this->assertSame(0, $server503ServiceUnavailableException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $server503ServiceUnavailableException = new Server503ServiceUnavailableException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $server503ServiceUnavailableException->getMessage());
            $this->assertSame($exception, $server503ServiceUnavailableException->getPrevious());
            $this->assertSame(0, $server503ServiceUnavailableException->getCode());
        }
    }
}
