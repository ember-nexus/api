<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\UserIdAndTokenIdObject;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(UserIdAndTokenIdObject::class)]
class UserIdAndTokenIdObjectTest extends TestCase
{
    public function testUserIdAndTokenIdObject(): void
    {
        $userId1 = Uuid::fromString('02IiNr0G8viOVKjPx5z6QecgygAzIOsJrfUCG');
        $userId2 = Uuid::fromString('02IiNr0G8viOVKkod8WWRSGSDwf7mLq5WEq1n');
        $tokenId1 = Uuid::fromString('02IiNr0G8viOVKjkBvyQV84O6GPpsvuY0lxGn');
        $tokenId2 = Uuid::fromString('02IiNr0G8viOVKk7LvQQUr5wXnnFmsrwdymRB');

        $userIdAndTokenIdObject = new UserIdAndTokenIdObject(
            $userId1,
            $tokenId1
        );

        $this->assertSame($userId1, $userIdAndTokenIdObject->getUserId());
        $this->assertSame($tokenId1, $userIdAndTokenIdObject->getTokenId());

        $userIdAndTokenIdObject->setUserId($userId2);
        $userIdAndTokenIdObject->setTokenId($tokenId2);

        $this->assertSame($userId2, $userIdAndTokenIdObject->getUserId());
        $this->assertSame($tokenId2, $userIdAndTokenIdObject->getTokenId());
    }
}
