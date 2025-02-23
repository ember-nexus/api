<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client412PreconditionFailedException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client412PreconditionFailedException::class)]
class Client412PreconditionFailedExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client412PreconditionFailedException = new Client412PreconditionFailedException(
            'type',
        );

        $this->assertSame('type', $client412PreconditionFailedException->getType());
        $this->assertSame('Precondition Failed', $client412PreconditionFailedException->getTitle());
        $this->assertSame(412, $client412PreconditionFailedException->getStatus());
        $this->assertSame('Precondition does not match.', $client412PreconditionFailedException->getDetail());
        $this->assertNull($client412PreconditionFailedException->getInstance());
        $this->assertSame('', $client412PreconditionFailedException->getMessage());
        $this->assertNull($client412PreconditionFailedException->getPrevious());
        $this->assertSame(0, $client412PreconditionFailedException->getCode());
    }

    public function testGetter(): void
    {
        $client412PreconditionFailedException = new Client412PreconditionFailedException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client412PreconditionFailedException->getType());
        $this->assertSame('title', $client412PreconditionFailedException->getTitle());
        $this->assertSame(123, $client412PreconditionFailedException->getStatus());
        $this->assertSame('detail', $client412PreconditionFailedException->getDetail());
        $this->assertSame('instance', $client412PreconditionFailedException->getInstance());
        $this->assertSame('', $client412PreconditionFailedException->getMessage());
        $this->assertNull($client412PreconditionFailedException->getPrevious());
        $this->assertSame(0, $client412PreconditionFailedException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client412PreconditionFailedException = new Client412PreconditionFailedException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client412PreconditionFailedException->getMessage());
            $this->assertSame($exception, $client412PreconditionFailedException->getPrevious());
            $this->assertSame(0, $client412PreconditionFailedException->getCode());
        }
    }
}
