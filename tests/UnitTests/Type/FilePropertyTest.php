<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Service\FileService;
use App\Type\FileProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(FileProperty::class)]
class FilePropertyTest extends TestCase
{
    public function testDefaultExtension(): void
    {
        $fileProperty = new FileProperty();

        $this->assertSame(FileService::DEFAULT_EXTENSION, $fileProperty->getExtension());
    }

    public function testSetExtensionReturnsStaticAndUpdatesValue(): void
    {
        $fileProperty = new FileProperty();
        $result = $fileProperty->setExtension('png');

        $this->assertSame($fileProperty, $result);
        $this->assertSame('png', $fileProperty->getExtension());
    }

    public function testJsonSerialize(): void
    {
        $fileProperty = (new FileProperty())->setExtension('jpg');

        $this->assertSame(['extension' => 'jpg'], $fileProperty->jsonSerialize());
        $this->assertSame('{"extension":"jpg"}', json_encode($fileProperty));
    }

    public function testJsonSerializeWithDefaultExtension(): void
    {
        $fileProperty = new FileProperty();

        $this->assertSame(['extension' => FileService::DEFAULT_EXTENSION], $fileProperty->jsonSerialize());
    }
}
