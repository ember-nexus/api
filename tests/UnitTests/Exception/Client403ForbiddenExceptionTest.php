<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client403ForbiddenException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client403ForbiddenException::class)]
class Client403ForbiddenExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client403ForbiddenException = new Client403ForbiddenException(
            'type',
        );

        $this->assertSame('type', $client403ForbiddenException->getType());
        $this->assertSame('Forbidden', $client403ForbiddenException->getTitle());
        $this->assertSame(403, $client403ForbiddenException->getStatus());
        $this->assertSame('Requested endpoint, element or action is forbidden.', $client403ForbiddenException->getDetail());
        $this->assertNull($client403ForbiddenException->getInstance());
        $this->assertSame('', $client403ForbiddenException->getMessage());
        $this->assertNull($client403ForbiddenException->getPrevious());
        $this->assertSame(0, $client403ForbiddenException->getCode());
    }

    public function testGetter(): void
    {
        $client403ForbiddenException = new Client403ForbiddenException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client403ForbiddenException->getType());
        $this->assertSame('title', $client403ForbiddenException->getTitle());
        $this->assertSame(123, $client403ForbiddenException->getStatus());
        $this->assertSame('detail', $client403ForbiddenException->getDetail());
        $this->assertSame('instance', $client403ForbiddenException->getInstance());
        $this->assertSame('', $client403ForbiddenException->getMessage());
        $this->assertNull($client403ForbiddenException->getPrevious());
        $this->assertSame(0, $client403ForbiddenException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client403ForbiddenException = new Client403ForbiddenException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client403ForbiddenException->getMessage());
            $this->assertSame($exception, $client403ForbiddenException->getPrevious());
            $this->assertSame(0, $client403ForbiddenException->getCode());
        }
    }
}
