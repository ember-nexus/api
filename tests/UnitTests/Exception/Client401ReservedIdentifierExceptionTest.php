<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Exception;

use App\Exception\Client401UnauthorizedException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Client401UnauthorizedException::class)]
class Client401ReservedIdentifierExceptionTest extends TestCase
{
    public function testDefault(): void
    {
        $client401UnauthorizedException = new Client401UnauthorizedException(
            'type',
        );

        $this->assertSame('type', $client401UnauthorizedException->getType());
        $this->assertSame('Unauthorized', $client401UnauthorizedException->getTitle());
        $this->assertSame(401, $client401UnauthorizedException->getStatus());
        $this->assertSame('Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user\'s unique identifier, or the user\'s status (e.g., missing, blocked, or deleted).', $client401UnauthorizedException->getDetail());
        $this->assertNull($client401UnauthorizedException->getInstance());
        $this->assertSame('', $client401UnauthorizedException->getMessage());
        $this->assertNull($client401UnauthorizedException->getPrevious());
        $this->assertSame(0, $client401UnauthorizedException->getCode());
    }

    public function testGetter(): void
    {
        $client401UnauthorizedException = new Client401UnauthorizedException(
            'type',
            'title',
            123,
            'detail',
            'instance',
            null
        );

        $this->assertSame('type', $client401UnauthorizedException->getType());
        $this->assertSame('title', $client401UnauthorizedException->getTitle());
        $this->assertSame(123, $client401UnauthorizedException->getStatus());
        $this->assertSame('detail', $client401UnauthorizedException->getDetail());
        $this->assertSame('instance', $client401UnauthorizedException->getInstance());
        $this->assertSame('', $client401UnauthorizedException->getMessage());
        $this->assertNull($client401UnauthorizedException->getPrevious());
        $this->assertSame(0, $client401UnauthorizedException->getCode());
    }

    public function testWithPrevious(): void
    {
        try {
            throw new Exception('Test');
        } catch (Exception $exception) {
            $client401UnauthorizedException = new Client401UnauthorizedException(
                'type',
                'title',
                123,
                'detail',
                'instance',
                $exception
            );

            $this->assertSame('', $client401UnauthorizedException->getMessage());
            $this->assertSame($exception, $client401UnauthorizedException->getPrevious());
            $this->assertSame(0, $client401UnauthorizedException->getCode());
        }
    }
}
