<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client416RangeNotSatisfiableException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client416RangeNotSatisfiableException::class)]
class Client416RangeNotSatisfiableExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $exception = new Client416RangeNotSatisfiableException('type');

        $this->assertSame('type', $exception->getType());
        $this->assertSame('Range Not Satisfiable', $exception->getTitle());
        $this->assertSame(416, $exception->getStatus());
        $this->assertSame('The requested range can not be satisfied for the target resource.', $exception->getDetail());
        $this->assertNull($exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertNull($exception->getPrevious());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame([], $exception->getAdditionalProperties());
    }

    public function testGetter(): void
    {
        $exception = new Client416RangeNotSatisfiableException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null,
            ['foo' => 'bar'],
        );

        $this->assertSame('type', $exception->getType());
        $this->assertSame('title', $exception->getTitle());
        $this->assertSame(123, $exception->getStatus());
        $this->assertSame('detail', $exception->getDetail());
        $this->assertSame('instance', $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertNull($exception->getPrevious());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame(['foo' => 'bar'], $exception->getAdditionalProperties());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $previous) {
            $exception = new Client416RangeNotSatisfiableException(
                'type',
                previous: $previous,
            );

            $this->assertSame($previous, $exception->getPrevious());
        }
    }
}
