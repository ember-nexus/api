<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Trait;

use App\Trait\PropertiesTrait;
use Exception;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversTrait(PropertiesTrait::class)]
class PropertiesTraitTest extends TestCase
{
    private function createSubject(): object
    {
        return new class {
            use PropertiesTrait;
        };
    }

    public function testGetStringProperty(): void
    {
        $subject = $this->createSubject();
        $subject->addProperty('name', 'value');

        $this->assertSame('value', $subject->getStringProperty('name'));
    }

    public function testGetStringPropertyWithWrongType(): void
    {
        $subject = $this->createSubject();
        $subject->addProperty('name', 12);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected property "name" to be of type string, got int.');
        $subject->getStringProperty('name');
    }

    public function testGetStringPropertyWithMissingProperty(): void
    {
        $subject = $this->createSubject();

        $this->expectException(Exception::class);
        $subject->getStringProperty('name');
    }

    public function testGetNullableStringProperty(): void
    {
        $subject = $this->createSubject();
        $subject->addProperty('name', 'value');
        $subject->addProperty('empty', null);

        $this->assertSame('value', $subject->getNullableStringProperty('name'));
        $this->assertNull($subject->getNullableStringProperty('empty'));
        $this->assertNull($subject->getNullableStringProperty('missing'));
    }

    public function testGetNullableStringPropertyWithWrongType(): void
    {
        $subject = $this->createSubject();
        $subject->addProperty('name', ['a']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected property "name" to be of type string or null, got array.');
        $subject->getNullableStringProperty('name');
    }

    public function testGetArrayProperty(): void
    {
        $subject = $this->createSubject();
        $subject->addProperty('file', ['extension' => 'txt']);

        $this->assertSame(['extension' => 'txt'], $subject->getArrayProperty('file'));
    }

    public function testGetArrayPropertyWithWrongType(): void
    {
        $subject = $this->createSubject();
        $subject->addProperty('file', 'text');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected property "file" to be of type array, got string.');
        $subject->getArrayProperty('file');
    }
}
