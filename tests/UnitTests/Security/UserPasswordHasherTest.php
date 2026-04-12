<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Security;

use App\Security\UserPasswordHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(UserPasswordHasher::class)]
class UserPasswordHasherTest extends TestCase
{
    public function testHashing(): void
    {
        $userPasswordHasher = new UserPasswordHasher();
        $hashedPassword = $userPasswordHasher->hashPassword('1234');
        $this->assertNotSame('1234', $hashedPassword);
        $this->assertTrue($userPasswordHasher->verifyPassword('1234', $hashedPassword));
        // verify that hashing the same password twice does not result in collision / identical hash
        $this->assertNotSame($hashedPassword, $userPasswordHasher->hashPassword('1234'));
    }
}
