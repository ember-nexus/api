<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Exception;

use App\Exception\Client409ConflictException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client409ConflictException::class)]
class Client409ConflictExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client409ConflictException = new Client409ConflictException(
            'type',
        );

        $this->assertSame('type', $client409ConflictException->getType());
        $this->assertSame('Conflict', $client409ConflictException->getTitle());
        $this->assertSame(409, $client409ConflictException->getStatus());
        $this->assertSame('Operation can not be performed due to some sort of conflict.', $client409ConflictException->getDetail());
        $this->assertNull($client409ConflictException->getInstance());
        $this->assertSame('', $client409ConflictException->getMessage());
        $this->assertNull($client409ConflictException->getPrevious());
        $this->assertSame(0, $client409ConflictException->getCode());
    }

    public function testGetter(): void
    {
        $client409ConflictException = new Client409ConflictException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client409ConflictException->getType());
        $this->assertSame('title', $client409ConflictException->getTitle());
        $this->assertSame(123, $client409ConflictException->getStatus());
        $this->assertSame('detail', $client409ConflictException->getDetail());
        $this->assertSame('instance', $client409ConflictException->getInstance());
        $this->assertSame('', $client409ConflictException->getMessage());
        $this->assertNull($client409ConflictException->getPrevious());
        $this->assertSame(0, $client409ConflictException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client409ConflictException = new Client409ConflictException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client409ConflictException->getMessage());
            $this->assertSame($exception, $client409ConflictException->getPrevious());
            $this->assertSame(0, $client409ConflictException->getCode());
        }
    }
}
