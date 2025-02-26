<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client400BadContentException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client400BadContentException::class)]
class Client400BadContentExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client400BadContentException = new Client400BadContentException(
            'type'
        );

        $this->assertSame('type', $client400BadContentException->getType());
        $this->assertSame('Bad content', $client400BadContentException->getTitle());
        $this->assertSame(400, $client400BadContentException->getStatus());
        $this->assertSame('', $client400BadContentException->getDetail());
        $this->assertNull($client400BadContentException->getInstance());
        $this->assertSame('', $client400BadContentException->getMessage());
        $this->assertNull($client400BadContentException->getPrevious());
        $this->assertSame(0, $client400BadContentException->getCode());
    }

    public function testGetter(): void
    {
        $client400BadContentException = new Client400BadContentException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client400BadContentException->getType());
        $this->assertSame('title', $client400BadContentException->getTitle());
        $this->assertSame(123, $client400BadContentException->getStatus());
        $this->assertSame('detail', $client400BadContentException->getDetail());
        $this->assertSame('instance', $client400BadContentException->getInstance());
        $this->assertSame('', $client400BadContentException->getMessage());
        $this->assertNull($client400BadContentException->getPrevious());
        $this->assertSame(0, $client400BadContentException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client400BadContentException = new Client400BadContentException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client400BadContentException->getMessage());
            $this->assertSame($exception, $client400BadContentException->getPrevious());
            $this->assertSame(0, $client400BadContentException->getCode());
        }
    }
}
