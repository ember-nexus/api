<?php

namespace App\tests\UnitTests\Attribute;

use App\Attribute\EndpointSupportsEtag;
use App\Type\EtagType;
use PHPUnit\Framework\TestCase;

class EndpointSupportsEtagTest extends TestCase
{
    public function testEndpointSupportsEtag(): void
    {
        $attribute = new EndpointSupportsEtag(EtagType::ELEMENT);
        $this->assertSame(EtagType::ELEMENT, $attribute->getEtagType());
    }
}
