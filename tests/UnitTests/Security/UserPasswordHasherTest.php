<?php

namespace App\tests\UnitTests\Security;

use App\Security\UserPasswordHasher;
use PHPUnit\Framework\TestCase;

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
