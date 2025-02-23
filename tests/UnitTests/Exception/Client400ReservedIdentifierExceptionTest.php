<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client400ReservedIdentifierException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client400ReservedIdentifierException::class)]
class Client400ReservedIdentifierExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client400ReservedIdentifierException = new Client400ReservedIdentifierException(
            'type',
        );

        $this->assertSame('type', $client400ReservedIdentifierException->getType());
        $this->assertSame('Reserved identifier', $client400ReservedIdentifierException->getTitle());
        $this->assertSame(400, $client400ReservedIdentifierException->getStatus());
        $this->assertSame('', $client400ReservedIdentifierException->getDetail());
        $this->assertNull($client400ReservedIdentifierException->getInstance());
        $this->assertSame('', $client400ReservedIdentifierException->getMessage());
        $this->assertNull($client400ReservedIdentifierException->getPrevious());
        $this->assertSame(0, $client400ReservedIdentifierException->getCode());
    }

    public function testGetter(): void
    {
        $client400ReservedIdentifierException = new Client400ReservedIdentifierException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client400ReservedIdentifierException->getType());
        $this->assertSame('title', $client400ReservedIdentifierException->getTitle());
        $this->assertSame(123, $client400ReservedIdentifierException->getStatus());
        $this->assertSame('detail', $client400ReservedIdentifierException->getDetail());
        $this->assertSame('instance', $client400ReservedIdentifierException->getInstance());
        $this->assertSame('', $client400ReservedIdentifierException->getMessage());
        $this->assertNull($client400ReservedIdentifierException->getPrevious());
        $this->assertSame(0, $client400ReservedIdentifierException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client400ReservedIdentifierException = new Client400ReservedIdentifierException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client400ReservedIdentifierException->getMessage());
            $this->assertSame($exception, $client400ReservedIdentifierException->getPrevious());
            $this->assertSame(0, $client400ReservedIdentifierException->getCode());
        }
    }
}
