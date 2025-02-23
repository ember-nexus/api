<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client400IncompleteMutualDependencyException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client400IncompleteMutualDependencyException::class)]
class Client400IncompleteMutualDependencyExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client400IncompleteMutualDependencyException = new Client400IncompleteMutualDependencyException(
            'type',
        );

        $this->assertSame('type', $client400IncompleteMutualDependencyException->getType());
        $this->assertSame('Incomplete mutual dependency', $client400IncompleteMutualDependencyException->getTitle());
        $this->assertSame(400, $client400IncompleteMutualDependencyException->getStatus());
        $this->assertSame('', $client400IncompleteMutualDependencyException->getDetail());
        $this->assertNull($client400IncompleteMutualDependencyException->getInstance());
        $this->assertSame('', $client400IncompleteMutualDependencyException->getMessage());
        $this->assertNull($client400IncompleteMutualDependencyException->getPrevious());
        $this->assertSame(0, $client400IncompleteMutualDependencyException->getCode());
    }

    public function testGetter(): void
    {
        $client400IncompleteMutualDependencyException = new Client400IncompleteMutualDependencyException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client400IncompleteMutualDependencyException->getType());
        $this->assertSame('title', $client400IncompleteMutualDependencyException->getTitle());
        $this->assertSame(123, $client400IncompleteMutualDependencyException->getStatus());
        $this->assertSame('detail', $client400IncompleteMutualDependencyException->getDetail());
        $this->assertSame('instance', $client400IncompleteMutualDependencyException->getInstance());
        $this->assertSame('', $client400IncompleteMutualDependencyException->getMessage());
        $this->assertNull($client400IncompleteMutualDependencyException->getPrevious());
        $this->assertSame(0, $client400IncompleteMutualDependencyException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client400IncompleteMutualDependencyException = new Client400IncompleteMutualDependencyException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client400IncompleteMutualDependencyException->getMessage());
            $this->assertSame($exception, $client400IncompleteMutualDependencyException->getPrevious());
            $this->assertSame(0, $client400IncompleteMutualDependencyException->getCode());
        }
    }
}
