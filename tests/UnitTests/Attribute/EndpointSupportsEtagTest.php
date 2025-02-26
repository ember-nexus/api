<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Attribute;

use App\Attribute\EndpointSupportsEtag;
use App\Type\EtagType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(EndpointSupportsEtag::class)]
class EndpointSupportsEtagTest extends TestCase
{
    public function testEndpointSupportsEtag(): void
    {
        $attribute = new EndpointSupportsEtag(EtagType::ELEMENT);
        $this->assertSame(EtagType::ELEMENT, $attribute->getEtagType());
    }
}
