<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client422UnprocessableContentException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client422UnprocessableContentException::class)]
class Client422UnprocessableContentExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client422UnprocessableContentException = new Client422UnprocessableContentException(
            'type',
        );

        $this->assertSame('type', $client422UnprocessableContentException->getType());
        $this->assertSame('Unprocessable content', $client422UnprocessableContentException->getTitle());
        $this->assertSame(422, $client422UnprocessableContentException->getStatus());
        $this->assertSame('The request content was understood, but could not be processed.', $client422UnprocessableContentException->getDetail());
        $this->assertNull($client422UnprocessableContentException->getInstance());
        $this->assertSame('', $client422UnprocessableContentException->getMessage());
        $this->assertNull($client422UnprocessableContentException->getPrevious());
        $this->assertSame(0, $client422UnprocessableContentException->getCode());
    }

    public function testGetter(): void
    {
        $client422UnprocessableContentException = new Client422UnprocessableContentException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client422UnprocessableContentException->getType());
        $this->assertSame('title', $client422UnprocessableContentException->getTitle());
        $this->assertSame(123, $client422UnprocessableContentException->getStatus());
        $this->assertSame('detail', $client422UnprocessableContentException->getDetail());
        $this->assertSame('instance', $client422UnprocessableContentException->getInstance());
        $this->assertSame('', $client422UnprocessableContentException->getMessage());
        $this->assertNull($client422UnprocessableContentException->getPrevious());
        $this->assertSame(0, $client422UnprocessableContentException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client422UnprocessableContentException = new Client422UnprocessableContentException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client422UnprocessableContentException->getMessage());
            $this->assertSame($exception, $client422UnprocessableContentException->getPrevious());
            $this->assertSame(0, $client422UnprocessableContentException->getCode());
        }
    }
}
