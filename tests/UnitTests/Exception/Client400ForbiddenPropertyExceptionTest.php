<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client400ForbiddenPropertyException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client400ForbiddenPropertyException::class)]
class Client400ForbiddenPropertyExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client400ForbiddenPropertyException = new Client400ForbiddenPropertyException(
            'type',
        );

        $this->assertSame('type', $client400ForbiddenPropertyException->getType());
        $this->assertSame('Forbidden property', $client400ForbiddenPropertyException->getTitle());
        $this->assertSame(400, $client400ForbiddenPropertyException->getStatus());
        $this->assertSame('', $client400ForbiddenPropertyException->getDetail());
        $this->assertNull($client400ForbiddenPropertyException->getInstance());
        $this->assertSame('', $client400ForbiddenPropertyException->getMessage());
        $this->assertNull($client400ForbiddenPropertyException->getPrevious());
        $this->assertSame(0, $client400ForbiddenPropertyException->getCode());
    }

    public function testGetter(): void
    {
        $client400ForbiddenPropertyException = new Client400ForbiddenPropertyException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client400ForbiddenPropertyException->getType());
        $this->assertSame('title', $client400ForbiddenPropertyException->getTitle());
        $this->assertSame(123, $client400ForbiddenPropertyException->getStatus());
        $this->assertSame('detail', $client400ForbiddenPropertyException->getDetail());
        $this->assertSame('instance', $client400ForbiddenPropertyException->getInstance());
        $this->assertSame('', $client400ForbiddenPropertyException->getMessage());
        $this->assertNull($client400ForbiddenPropertyException->getPrevious());
        $this->assertSame(0, $client400ForbiddenPropertyException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client400ForbiddenPropertyException = new Client400ForbiddenPropertyException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client400ForbiddenPropertyException->getMessage());
            $this->assertSame($exception, $client400ForbiddenPropertyException->getPrevious());
            $this->assertSame(0, $client400ForbiddenPropertyException->getCode());
        }
    }
}
