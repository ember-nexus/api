<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\ProblemJsonException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ProblemJsonException::class)]
class ProblemJsonExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $problemJsonException = new ProblemJsonException(
            'type',
            'title',
            123,
            'detail',
        );

        $this->assertSame('type', $problemJsonException->getType());
        $this->assertSame('title', $problemJsonException->getTitle());
        $this->assertSame(123, $problemJsonException->getStatus());
        $this->assertSame('detail', $problemJsonException->getDetail());
        $this->assertNull($problemJsonException->getInstance());
        $this->assertSame('', $problemJsonException->getMessage());
        $this->assertNull($problemJsonException->getPrevious());
        $this->assertSame(0, $problemJsonException->getCode());
        $this->assertSame([], $problemJsonException->getAdditionalProperties());
    }

    public function testGetter(): void
    {
        $problemJsonException = new ProblemJsonException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null,
            [
                'a' => 'b',
            ]
        );

        $this->assertSame('type', $problemJsonException->getType());
        $this->assertSame('title', $problemJsonException->getTitle());
        $this->assertSame(123, $problemJsonException->getStatus());
        $this->assertSame('detail', $problemJsonException->getDetail());
        $this->assertSame('instance', $problemJsonException->getInstance());
        $this->assertSame('', $problemJsonException->getMessage());
        $this->assertNull($problemJsonException->getPrevious());
        $this->assertSame(0, $problemJsonException->getCode());
        $this->assertSame(['a' => 'b'], $problemJsonException->getAdditionalProperties());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $problemJsonException = new ProblemJsonException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $problemJsonException->getMessage());
            $this->assertSame($exception, $problemJsonException->getPrevious());
            $this->assertSame(0, $problemJsonException->getCode());
        }
    }
}
