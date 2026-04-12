<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client400ReservedTypeException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client400ReservedTypeException::class)]
class Client400ReservedTypeExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client400ReservedTypeException = new Client400ReservedTypeException(
            'type',
        );

        $this->assertSame('type', $client400ReservedTypeException->getType());
        $this->assertSame('Reserved type', $client400ReservedTypeException->getTitle());
        $this->assertSame(400, $client400ReservedTypeException->getStatus());
        $this->assertSame('', $client400ReservedTypeException->getDetail());
        $this->assertNull($client400ReservedTypeException->getInstance());
        $this->assertSame('', $client400ReservedTypeException->getMessage());
        $this->assertNull($client400ReservedTypeException->getPrevious());
        $this->assertSame(0, $client400ReservedTypeException->getCode());
    }

    public function testGetter(): void
    {
        $client400ReservedTypeException = new Client400ReservedTypeException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client400ReservedTypeException->getType());
        $this->assertSame('title', $client400ReservedTypeException->getTitle());
        $this->assertSame(123, $client400ReservedTypeException->getStatus());
        $this->assertSame('detail', $client400ReservedTypeException->getDetail());
        $this->assertSame('instance', $client400ReservedTypeException->getInstance());
        $this->assertSame('', $client400ReservedTypeException->getMessage());
        $this->assertNull($client400ReservedTypeException->getPrevious());
        $this->assertSame(0, $client400ReservedTypeException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client400ReservedTypeException = new Client400ReservedTypeException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client400ReservedTypeException->getMessage());
            $this->assertSame($exception, $client400ReservedTypeException->getPrevious());
            $this->assertSame(0, $client400ReservedTypeException->getCode());
        }
    }
}
