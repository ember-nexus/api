<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client400MissingPropertyException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client400MissingPropertyException::class)]
class Client400MissingPropertyExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client400MissingPropertyException = new Client400MissingPropertyException(
            'type',
        );

        $this->assertSame('type', $client400MissingPropertyException->getType());
        $this->assertSame('Missing property', $client400MissingPropertyException->getTitle());
        $this->assertSame(400, $client400MissingPropertyException->getStatus());
        $this->assertSame('', $client400MissingPropertyException->getDetail());
        $this->assertNull($client400MissingPropertyException->getInstance());
        $this->assertSame('', $client400MissingPropertyException->getMessage());
        $this->assertNull($client400MissingPropertyException->getPrevious());
        $this->assertSame(0, $client400MissingPropertyException->getCode());
    }

    public function testGetter(): void
    {
        $client400MissingPropertyException = new Client400MissingPropertyException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client400MissingPropertyException->getType());
        $this->assertSame('title', $client400MissingPropertyException->getTitle());
        $this->assertSame(123, $client400MissingPropertyException->getStatus());
        $this->assertSame('detail', $client400MissingPropertyException->getDetail());
        $this->assertSame('instance', $client400MissingPropertyException->getInstance());
        $this->assertSame('', $client400MissingPropertyException->getMessage());
        $this->assertNull($client400MissingPropertyException->getPrevious());
        $this->assertSame(0, $client400MissingPropertyException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client400MissingPropertyException = new Client400MissingPropertyException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client400MissingPropertyException->getMessage());
            $this->assertSame($exception, $client400MissingPropertyException->getPrevious());
            $this->assertSame(0, $client400MissingPropertyException->getCode());
        }
    }
}
