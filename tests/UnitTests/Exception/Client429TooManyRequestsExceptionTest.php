<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client429TooManyRequestsException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client429TooManyRequestsException::class)]
class Client429TooManyRequestsExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client429TooManyRequestsException = new Client429TooManyRequestsException(
            'type',
        );

        $this->assertSame('type', $client429TooManyRequestsException->getType());
        $this->assertSame('Too many requests', $client429TooManyRequestsException->getTitle());
        $this->assertSame(429, $client429TooManyRequestsException->getStatus());
        $this->assertSame('You have sent too many requests, please slow down.', $client429TooManyRequestsException->getDetail());
        $this->assertNull($client429TooManyRequestsException->getInstance());
        $this->assertSame('', $client429TooManyRequestsException->getMessage());
        $this->assertNull($client429TooManyRequestsException->getPrevious());
        $this->assertSame(0, $client429TooManyRequestsException->getCode());
    }

    public function testGetter(): void
    {
        $client429TooManyRequestsException = new Client429TooManyRequestsException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client429TooManyRequestsException->getType());
        $this->assertSame('title', $client429TooManyRequestsException->getTitle());
        $this->assertSame(123, $client429TooManyRequestsException->getStatus());
        $this->assertSame('detail', $client429TooManyRequestsException->getDetail());
        $this->assertSame('instance', $client429TooManyRequestsException->getInstance());
        $this->assertSame('', $client429TooManyRequestsException->getMessage());
        $this->assertNull($client429TooManyRequestsException->getPrevious());
        $this->assertSame(0, $client429TooManyRequestsException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client429TooManyRequestsException = new Client429TooManyRequestsException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client429TooManyRequestsException->getMessage());
            $this->assertSame($exception, $client429TooManyRequestsException->getPrevious());
            $this->assertSame(0, $client429TooManyRequestsException->getCode());
        }
    }
}
