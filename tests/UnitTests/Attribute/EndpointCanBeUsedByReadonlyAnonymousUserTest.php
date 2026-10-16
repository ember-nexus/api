<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Attribute;

use App\Attribute\EndpointCanBeUsedByReadonlyAnonymousUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(EndpointCanBeUsedByReadonlyAnonymousUser::class)]
class EndpointCanBeUsedByReadonlyAnonymousUserTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(EndpointCanBeUsedByReadonlyAnonymousUser::class, new EndpointCanBeUsedByReadonlyAnonymousUser());
    }
}
