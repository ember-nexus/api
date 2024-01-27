<?php

namespace App\tests\UnitTests\Type;

use App\Type\UserUuidAndTokenUuidObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UserUuidAndTokenUuidObjectTest extends TestCase
{
    public function testUserUuidAndTokenUuidObject(): void
    {
        $userUuid1 = Uuid::fromString('09c8325c-4029-4c0a-93db-e695de6a6516');
        $userUuid2 = Uuid::fromString('bbc04d1f-bd5a-45c9-8633-5137788bef52');
        $tokenUuid1 = Uuid::fromString('335865b2-c42f-4c1a-a7bd-d7e8d3df40fb');
        $tokenUuid2 = Uuid::fromString('62e90895-2b86-4a87-8f82-a9d498a56053');

        $userUuidAndTokenUuidObject = new UserUuidAndTokenUuidObject(
            $userUuid1,
            $tokenUuid1
        );

        $this->assertSame($userUuid1, $userUuidAndTokenUuidObject->getUserUuid());
        $this->assertSame($tokenUuid1, $userUuidAndTokenUuidObject->getTokenUuid());

        $userUuidAndTokenUuidObject->setUserUuid($userUuid2);
        $userUuidAndTokenUuidObject->setTokenUuid($tokenUuid2);

        $this->assertSame($userUuid2, $userUuidAndTokenUuidObject->getUserUuid());
        $this->assertSame($tokenUuid2, $userUuidAndTokenUuidObject->getTokenUuid());
    }
}
