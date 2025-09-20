<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Exception;

use App\Exception\Client400BadGrammarException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client400BadGrammarException::class)]
class Client400BadGrammarExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client400BadGrammarException = new Client400BadGrammarException(
            'type'
        );

        $this->assertSame('type', $client400BadGrammarException->getType());
        $this->assertSame('Bad grammar', $client400BadGrammarException->getTitle());
        $this->assertSame(400, $client400BadGrammarException->getStatus());
        $this->assertSame('', $client400BadGrammarException->getDetail());
        $this->assertNull($client400BadGrammarException->getInstance());
        $this->assertSame('', $client400BadGrammarException->getMessage());
        $this->assertNull($client400BadGrammarException->getPrevious());
        $this->assertSame(0, $client400BadGrammarException->getCode());
    }

    public function testGetter(): void
    {
        $client400BadGrammarException = new Client400BadGrammarException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client400BadGrammarException->getType());
        $this->assertSame('title', $client400BadGrammarException->getTitle());
        $this->assertSame(123, $client400BadGrammarException->getStatus());
        $this->assertSame('detail', $client400BadGrammarException->getDetail());
        $this->assertSame('instance', $client400BadGrammarException->getInstance());
        $this->assertSame('', $client400BadGrammarException->getMessage());
        $this->assertNull($client400BadGrammarException->getPrevious());
        $this->assertSame(0, $client400BadGrammarException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client400BadGrammarException = new Client400BadGrammarException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client400BadGrammarException->getMessage());
            $this->assertSame($exception, $client400BadGrammarException->getPrevious());
            $this->assertSame(0, $client400BadGrammarException->getCode());
        }
    }
}
