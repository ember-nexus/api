<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Attribute;

use App\Attribute\EndpointWithoutAuthentication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(EndpointWithoutAuthentication::class)]
class EndpointWithoutAuthenticationTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(EndpointWithoutAuthentication::class, new EndpointWithoutAuthentication());
    }
}
