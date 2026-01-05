<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Type;

use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\UploadElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;

#[Small]
#[CoversClass(UploadElement::class)]
#[Group('test')]
class UploadElementTest extends TestCase
{
    public function testUploadLength(): void
    {
        $uploadElement = new UploadElement();
        $this->assertNull($uploadElement->getUploadLength());
        $uploadElement->setUploadLength(1234);
        $this->assertSame(1234, $uploadElement->getUploadLength());
        $this->assertTrue($uploadElement->hasProperty('uploadLength'));
        $this->assertSame(1234, $uploadElement->getProperty('uploadLength'));
    }

    public function testUploadOffset(): void
    {
        $uploadElement = new UploadElement();
        $this->assertSame(0, $uploadElement->getUploadOffset());
        $uploadElement->setUploadOffset(3456);
        $this->assertSame(3456, $uploadElement->getUploadOffset());
        $this->assertTrue($uploadElement->hasProperty('uploadOffset'));
        $this->assertSame(3456, $uploadElement->getProperty('uploadOffset'));
    }

    public function testUploadComplete(): void
    {
        $uploadElement = new UploadElement();
        $this->assertFalse($uploadElement->isUploadComplete());
        $uploadElement->setUploadComplete(true);
        $this->assertTrue($uploadElement->isUploadComplete());
        $this->assertTrue($uploadElement->hasProperty('uploadComplete'));
        $this->assertTrue($uploadElement->getProperty('uploadComplete'));
        $uploadElement->setUploadComplete(false);
        $this->assertFalse($uploadElement->isUploadComplete());
        $this->assertTrue($uploadElement->hasProperty('uploadComplete'));
        $this->assertFalse($uploadElement->getProperty('uploadComplete'));
    }

    public function testUploadTarget(): void
    {
        $uploadTarget = Uuid::fromString('6e68d781-7f9b-402e-a502-63a5b0453f07');

        $uploadElement = new UploadElement();
        $this->assertNull($uploadElement->getUploadTarget());
        $uploadElement->setUploadTarget($uploadTarget);
        $this->assertSame($uploadTarget, $uploadElement->getUploadTarget());
        $this->assertTrue($uploadElement->hasProperty('uploadTarget'));
        $this->assertSame($uploadTarget, $uploadElement->getProperty('uploadTarget'));
    }

    public function testAlreadyUploadedChunks(): void
    {
        $uploadElement = new UploadElement();
        $this->assertSame(0, $uploadElement->getAlreadyUploadedChunks());
        $uploadElement->setAlreadyUploadedChunks(3);
        $this->assertSame(3, $uploadElement->getAlreadyUploadedChunks());
        $this->assertTrue($uploadElement->hasProperty('alreadyUploadedChunks'));
        $this->assertSame(3, $uploadElement->getProperty('alreadyUploadedChunks'));
    }

    public function testUploadOwner(): void
    {
        $uploadOwner = Uuid::fromString('32cc8f83-c284-41da-8c60-c36a68cd2b50');

        $uploadElement = new UploadElement();
        $this->assertNull($uploadElement->getUploadOwner());
        $uploadElement->setUploadOwner($uploadOwner);
        $this->assertSame($uploadOwner, $uploadElement->getUploadOwner());
        $this->assertTrue($uploadElement->hasProperty('uploadOwner'));
        $this->assertSame($uploadOwner, $uploadElement->getProperty('uploadOwner'));
    }

    public function testCreated(): void
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', '2026-01-05 20:41:00');

        $uploadElement = new UploadElement();
        $this->assertNull($uploadElement->getCreated());
        $uploadElement->setCreated($dateTime);
        $this->assertSame($dateTime, $uploadElement->getCreated());
        $this->assertTrue($uploadElement->hasProperty('created'));
        $this->assertSame($dateTime, $uploadElement->getProperty('created'));
    }

    public function testUpdated(): void
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', '2026-01-05 20:42:00');

        $uploadElement = new UploadElement();
        $this->assertNull($uploadElement->getUpdated());
        $uploadElement->setUpdated($dateTime);
        $this->assertSame($dateTime, $uploadElement->getUpdated());
        $this->assertTrue($uploadElement->hasProperty('updated'));
        $this->assertSame($dateTime, $uploadElement->getProperty('updated'));
    }

    public function testExpires(): void
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', '2026-01-05 20:43:00');

        $uploadElement = new UploadElement();
        $this->assertNull($uploadElement->getExpires());
        $uploadElement->setExpires($dateTime);
        $this->assertSame($dateTime, $uploadElement->getExpires());
        $this->assertTrue($uploadElement->hasProperty('expires'));
        $this->assertSame($dateTime, $uploadElement->getProperty('expires'));
    }

    public function testCreateFromElementThrowsOnRelation(): void
    {
        $element = new RelationElement();

        $this->expectExceptionMessage('Upload element must be a node, not a relation.');
        UploadElement::createFromElement($element);
    }

    public function testCreateFromElementThrowsIfTypeIsNotUpload(): void
    {
        $element = new NodeElement();
        $element->setLabel('NotAnUpload');

        $this->expectExceptionMessage('Can not cast element of type NotAnUpload to upload.');
        UploadElement::createFromElement($element);
    }

    public function testCreateFromElementHandlesEmptyUploadGracefully(): void
    {
        $element = new NodeElement();
        $element->setLabel('Upload');

        $uploadElement = UploadElement::createFromElement($element);
        $this->assertNull($uploadElement->getUploadLength());
        $this->assertSame(0, $uploadElement->getUploadOffset());
        $this->assertFalse($uploadElement->isUploadComplete());
        $this->assertNull($uploadElement->getUploadTarget());
        $this->assertSame(0, $uploadElement->getAlreadyUploadedChunks());
        $this->assertNull($uploadElement->getUploadOwner());
        $this->assertNull($uploadElement->getCreated());
        $this->assertNull($uploadElement->getUpdated());
        $this->assertNull($uploadElement->getExpires());
    }

    public function testCreateFromElementHandlesSetUploadGracefully(): void
    {
        $created = DateTime::createFromFormat('Y-m-d H:i:s', '2026-01-05 20:50:00');
        $updated = DateTime::createFromFormat('Y-m-d H:i:s', '2026-01-05 20:51:00');
        $expires = DateTime::createFromFormat('Y-m-d H:i:s', '2026-01-05 20:52:00');

        $element = new NodeElement();
        $element->setLabel('Upload');
        $element->addProperty('uploadLength', 1234);
        $element->addProperty('uploadOffset', 234);
        $element->addProperty('uploadComplete', false);
        $element->addProperty('uploadTarget', '0eff3cf1-0a0f-42f7-9fa5-c96124d674aa');
        $element->addProperty('alreadyUploadedChunks', 3);
        $element->addProperty('uploadOwner', '73868c92-b261-486f-926b-a05719ce7e6a');
        $element->addProperty('created', $created);
        $element->addProperty('updated', $updated);
        $element->addProperty('expires', $expires);

        $uploadElement = UploadElement::createFromElement($element);
        $this->assertSame(1234, $uploadElement->getUploadLength());
        $this->assertSame(234, $uploadElement->getUploadOffset());
        $this->assertFalse($uploadElement->isUploadComplete());
        $this->assertSame('0eff3cf1-0a0f-42f7-9fa5-c96124d674aa', $uploadElement->getUploadTarget()?->toString());
        $this->assertSame(3, $uploadElement->getAlreadyUploadedChunks());
        $this->assertSame('73868c92-b261-486f-926b-a05719ce7e6a', $uploadElement->getUploadOwner()?->toString());
        $this->assertSame($created, $uploadElement->getCreated());
        $this->assertSame($updated, $uploadElement->getUpdated());
        $this->assertSame($expires, $uploadElement->getExpires());
    }
}
