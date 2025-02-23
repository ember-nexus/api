<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client405MethodNotAllowedException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client405MethodNotAllowedException::class)]
class Client405MethodNotAllowedExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client405MethodNotAllowedException = new Client405MethodNotAllowedException(
            'type',
        );

        $this->assertSame('type', $client405MethodNotAllowedException->getType());
        $this->assertSame('Method not allowed', $client405MethodNotAllowedException->getTitle());
        $this->assertSame(405, $client405MethodNotAllowedException->getStatus());
        $this->assertSame('Endpoint does not support requested method, or you do not have sufficient permissions.', $client405MethodNotAllowedException->getDetail());
        $this->assertNull($client405MethodNotAllowedException->getInstance());
        $this->assertSame('', $client405MethodNotAllowedException->getMessage());
        $this->assertNull($client405MethodNotAllowedException->getPrevious());
        $this->assertSame(0, $client405MethodNotAllowedException->getCode());
    }

    public function testGetter(): void
    {
        $client405MethodNotAllowedException = new Client405MethodNotAllowedException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client405MethodNotAllowedException->getType());
        $this->assertSame('title', $client405MethodNotAllowedException->getTitle());
        $this->assertSame(123, $client405MethodNotAllowedException->getStatus());
        $this->assertSame('detail', $client405MethodNotAllowedException->getDetail());
        $this->assertSame('instance', $client405MethodNotAllowedException->getInstance());
        $this->assertSame('', $client405MethodNotAllowedException->getMessage());
        $this->assertNull($client405MethodNotAllowedException->getPrevious());
        $this->assertSame(0, $client405MethodNotAllowedException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client405MethodNotAllowedException = new Client405MethodNotAllowedException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client405MethodNotAllowedException->getMessage());
            $this->assertSame($exception, $client405MethodNotAllowedException->getPrevious());
            $this->assertSame(0, $client405MethodNotAllowedException->getCode());
        }
    }
}
