<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\ProblemJsonException;
use PHPUnit\Framework\TestCase;

class ProblemJsonExceptionTest extends TestCase
{
    public function testGetter(): void
    {
        $problemJsonException = new ProblemJsonException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $problemJsonException->getType());
        $this->assertSame('title', $problemJsonException->getTitle());
        $this->assertSame(123, $problemJsonException->getStatus());
        $this->assertSame('detail', $problemJsonException->getDetail());
        $this->assertSame('instance', $problemJsonException->getInstance());
        $this->assertNull($problemJsonException->getPrevious());
        $this->assertSame(0, $problemJsonException->getCode());
    }
}
