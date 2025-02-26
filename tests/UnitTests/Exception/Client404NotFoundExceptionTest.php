<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client404NotFoundException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client404NotFoundException::class)]
class Client404NotFoundExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client404NotFoundException = new Client404NotFoundException(
            'type',
        );

        $this->assertSame('type', $client404NotFoundException->getType());
        $this->assertSame('Not found', $client404NotFoundException->getTitle());
        $this->assertSame(404, $client404NotFoundException->getStatus());
        $this->assertSame('Requested element was not found.', $client404NotFoundException->getDetail());
        $this->assertNull($client404NotFoundException->getInstance());
        $this->assertSame('', $client404NotFoundException->getMessage());
        $this->assertNull($client404NotFoundException->getPrevious());
        $this->assertSame(0, $client404NotFoundException->getCode());
    }

    public function testGetter(): void
    {
        $client404NotFoundException = new Client404NotFoundException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client404NotFoundException->getType());
        $this->assertSame('title', $client404NotFoundException->getTitle());
        $this->assertSame(123, $client404NotFoundException->getStatus());
        $this->assertSame('detail', $client404NotFoundException->getDetail());
        $this->assertSame('instance', $client404NotFoundException->getInstance());
        $this->assertSame('', $client404NotFoundException->getMessage());
        $this->assertNull($client404NotFoundException->getPrevious());
        $this->assertSame(0, $client404NotFoundException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client404NotFoundException = new Client404NotFoundException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client404NotFoundException->getMessage());
            $this->assertSame($exception, $client404NotFoundException->getPrevious());
            $this->assertSame(0, $client404NotFoundException->getCode());
        }
    }
}
