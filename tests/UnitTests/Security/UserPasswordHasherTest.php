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
        $this->assertStringStartsWith('$argon2id$', $hashedPassword);
        $this->assertTrue($userPasswordHasher->verifyPassword('1234', $hashedPassword));
        // verify that hashing the same password twice does not result in collision / identical hash
        $this->assertNotSame($hashedPassword, $userPasswordHasher->hashPassword('1234'));
    }

    public function testLegacyArgon2iHashStillVerifiesAndNeedsRehash(): void
    {
        $userPasswordHasher = new UserPasswordHasher();
        $legacyHash = password_hash('1234', PASSWORD_ARGON2I);
        $this->assertTrue($userPasswordHasher->verifyPassword('1234', $legacyHash));
        $this->assertFalse($userPasswordHasher->verifyPassword('wrong', $legacyHash));
        $this->assertTrue($userPasswordHasher->needsRehash($legacyHash));
        $this->assertFalse($userPasswordHasher->needsRehash($userPasswordHasher->hashPassword('1234')));
    }

    public function testDummyVerificationCostsAsMuchAsARealOne(): void
    {
        $hasher = new UserPasswordHasher();
        $realHash = $hasher->hashPassword('real-password');
        $hasher->verifyAgainstDummyHash('warm up'); // creates the dummy hash

        $time = static function (callable $callback): float {
            $start = hrtime(true);
            for ($i = 0; $i < 3; ++$i) {
                $callback();
            }

            return (hrtime(true) - $start) / 3e6;
        };
        $real = $time(static fn () => $hasher->verifyPassword('wrong', $realHash));
        $dummy = $time(static fn () => $hasher->verifyAgainstDummyHash('wrong'));

        $this->assertGreaterThan(0.5 * $real, $dummy);
        $this->assertLessThan(2 * $real, $dummy);
    }
}
