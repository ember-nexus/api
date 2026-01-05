<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Exception;

use App\Exception\Client410GoneException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client410GoneException::class)]
class Client410GoneExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client410GoneException = new Client410GoneException(
            'type',
        );

        $this->assertSame('type', $client410GoneException->getType());
        $this->assertSame('Gone', $client410GoneException->getTitle());
        $this->assertSame(410, $client410GoneException->getStatus());
        $this->assertSame('Requested resource is no longer available and is expected to soon be permanently deleted.', $client410GoneException->getDetail());
        $this->assertNull($client410GoneException->getInstance());
        $this->assertSame('', $client410GoneException->getMessage());
        $this->assertNull($client410GoneException->getPrevious());
        $this->assertSame(0, $client410GoneException->getCode());
    }

    public function testGetter(): void
    {
        $client410GoneException = new Client410GoneException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client410GoneException->getType());
        $this->assertSame('title', $client410GoneException->getTitle());
        $this->assertSame(123, $client410GoneException->getStatus());
        $this->assertSame('detail', $client410GoneException->getDetail());
        $this->assertSame('instance', $client410GoneException->getInstance());
        $this->assertSame('', $client410GoneException->getMessage());
        $this->assertNull($client410GoneException->getPrevious());
        $this->assertSame(0, $client410GoneException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client410GoneException = new Client410GoneException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client410GoneException->getMessage());
            $this->assertSame($exception, $client410GoneException->getPrevious());
            $this->assertSame(0, $client410GoneException->getCode());
        }
    }
}
